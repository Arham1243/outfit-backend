<?php

namespace App\Services\Fashn;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FashnClient
{
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $baseUrl = null,
    ) {}

    /**
     * @throws FashnException
     */
    public function modelCreate(?int $heightCm = null, ?string $gender = null, ?string $faceReference = null): string
    {
        $inputs = [
            'prompt' => $this->buildModelCreatePrompt($heightCm, $gender),
            'aspect_ratio' => config('services.fashn.model_create_aspect_ratio', '2:3'),
            'output_format' => 'jpeg',
            'resolution' => config('services.fashn.model_create_resolution', '2k'),
            'generation_mode' => config('services.fashn.model_create_generation_mode', 'balanced'),
        ];

        if ($faceReference !== null && $faceReference !== '') {
            $inputs['face_reference'] = $this->resolveImageInput($faceReference);
            $inputs['face_reference_mode'] = config(
                'services.fashn.face_reference_mode',
                'match_reference'
            );
        }

        return $this->extractImageUrl(
            $this->runAndWait('model-create', $inputs),
            'model-create'
        );
    }

    /**
     * @throws FashnException
     */
    public function tryOnMax(string $modelImage, string $productImage, ?string $prompt = null): string
    {
        $inputs = [
            'model_image' => $this->resolveImageInput($modelImage),
            'product_image' => $this->resolveImageInput($productImage),
            'output_format' => 'jpeg',
            'resolution' => config('services.fashn.resolution', '1k'),
            'generation_mode' => config('services.fashn.generation_mode', 'fast'),
        ];

        if ($prompt !== null && $prompt !== '') {
            $inputs['prompt'] = $prompt;
        }

        return $this->extractImageUrl(
            $this->runAndWait('tryon-max', $inputs),
            'tryon-max'
        );
    }

    /**
     * @throws FashnException
     */
    public function run(string $modelName, array $inputs): string
    {
        $response = Http::withToken($this->getApiKey())
            ->acceptJson()
            ->timeout(60)
            ->post($this->endpoint('/v1/run'), [
                'model_name' => $modelName,
                'inputs' => $inputs,
            ]);

        if (! $response->successful()) {
            throw FashnException::fromResponse($response->status(), $response->json(), $modelName);
        }

        $predictionId = $response->json('id');

        if (! is_string($predictionId) || $predictionId === '') {
            throw new FashnException('FASHN run response missing prediction id.', $modelName);
        }

        return $predictionId;
    }

    /**
     * @throws FashnException
     */
    public function waitForResult(string $predictionId): array
    {
        $timeoutSeconds = max(30, (int) config('services.fashn.poll_timeout_seconds', 600));
        $intervalSeconds = max(1, (int) config('services.fashn.poll_interval_seconds', 3));
        $deadline = time() + $timeoutSeconds;

        while (time() < $deadline) {
            $response = Http::withToken($this->getApiKey())
                ->acceptJson()
                ->timeout(30)
                ->get($this->endpoint('/v1/status/'.$predictionId));

            if ($response->status() === 429) {
                sleep($intervalSeconds);
                continue;
            }

            if (! $response->successful()) {
                throw FashnException::fromResponse($response->status(), $response->json());
            }

            $payload = $response->json();
            $status = (string) ($payload['status'] ?? '');

            if ($status === 'completed') {
                return is_array($payload) ? $payload : [];
            }

            if ($status === 'failed') {
                $error = $payload['error'] ?? [];
                $name = is_array($error) ? ($error['name'] ?? 'UnknownError') : 'UnknownError';
                $message = is_array($error) ? ($error['message'] ?? 'FASHN prediction failed.') : 'FASHN prediction failed.';

                Log::error('FASHN prediction failed.', [
                    'prediction_id' => $predictionId,
                    'error_name' => $name,
                    'error_message' => $message,
                ]);

                throw new FashnException($message, null, $name);
            }

            sleep($intervalSeconds);
        }

        throw new FashnException(sprintf('FASHN prediction timed out after %d seconds.', $timeoutSeconds));
    }

    /**
     * @throws FashnException
     */
    public function runAndWait(string $modelName, array $inputs): array
    {
        $attempts = max(1, (int) config('services.fashn.max_attempts', 2));
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $predictionId = $this->run($modelName, $inputs);

                return $this->waitForResult($predictionId);
            } catch (FashnException $exception) {
                $lastException = $exception;

                if ($attempt < $attempts && $exception->isRetryable()) {
                    $this->sleepBeforeRetry($attempt);
                    continue;
                }

                throw $exception;
            }
        }

        throw $lastException ?? new FashnException('FASHN runAndWait failed after retries.', $modelName);
    }

    public function resolveImageInput(string $image): string
    {
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, 'data:')) {
            return $image;
        }

        $contents = $this->readStoredImage($image);

        if ($contents === null) {
            throw new RuntimeException(sprintf('Image not found for FASHN input: %s', $image));
        }

        $mime = $this->guessMimeType($image, $contents);

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function readStoredImage(string $path): ?string
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->get($path);
        }

        if (Storage::disk('s3')->exists($path)) {
            return Storage::disk('s3')->get($path);
        }

        return null;
    }

    private function guessMimeType(string $path, string $contents): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }

    private function buildModelCreatePrompt(?int $heightCm, ?string $gender): string
    {
        $subject = match ($gender) {
            'female' => 'Full body shot of an adult woman',
            'male' => 'Full body shot of an adult man',
            default => 'Full body shot of an adult fashion model',
        };

        $parts = array_values(array_filter([
            $subject,
            'standing straight, neutral pose, plain white studio background',
            'wearing a plain white t-shirt and neutral shorts',
            'realistic human anatomy, natural head-to-body ratio, full-length fashion model photo',
            ($heightCm !== null && $heightCm > 0)
                ? sprintf('approximately %d cm tall, balanced body proportions', $heightCm)
                : null,
        ]));

        return implode(', ', $parts);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws FashnException
     */
    private function extractImageUrl(array $payload, string $modelName): string
    {
        $output = $payload['output'] ?? null;

        if (is_array($output) && isset($output[0]) && is_string($output[0])) {
            return $output[0];
        }

        throw new FashnException('FASHN completed response did not include an output image URL.', $modelName);
    }

    private function getApiKey(): string
    {
        $apiKey = $this->apiKey ?? config('services.fashn.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('FASHN_API_KEY is not configured.');
        }

        return $apiKey;
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim($this->baseUrl ?? config('services.fashn.base_url', 'https://api.fashn.ai'), '/');

        return $baseUrl.$path;
    }

    private function sleepBeforeRetry(int $attempt): void
    {
        sleep(min(30, $attempt * 5));
    }
}
