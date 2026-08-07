<?php

namespace App\Services\OpenAi;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiClient
{
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $baseUrl = null,
    ) {}

    /**
     * @param  list<string>  $referenceImages  Storage paths, URLs, or data URIs
     *
     * @throws OpenAiException
     */
    public function editImage(array $referenceImages, string $prompt, array $options = []): string
    {
        if ($referenceImages === []) {
            throw new OpenAiException('OpenAI image edit requires at least one reference image.', '/images/edits');
        }

        $request = Http::withToken($this->getApiKey())
            ->acceptJson()
            ->timeout(300);

        foreach ($referenceImages as $index => $image) {
            $contents = $this->resolveImageContents($image);
            $filename = sprintf('reference-%d.jpg', $index + 1);
            $request = $request->attach(
                'image[]',
                $contents['binary'],
                $filename,
                ['Content-Type' => $contents['mime']]
            );
        }

        $response = $request->post($this->endpoint('/images/edits'), [
            'model' => $options['model'] ?? config('services.openai.image_model', 'gpt-image-2'),
            'prompt' => $prompt,
            'size' => $options['size'] ?? config('services.openai.image_size', '1024x1536'),
            'quality' => $options['quality'] ?? config('services.openai.image_quality', 'high'),
            'n' => 1,
        ]);

        return $this->persistImageResponse($response, '/images/edits');
    }

    /**
     * @param  list<array<string, mixed>>  $wardrobeItems
     * @param  array<string, mixed>  $preferences
     * @return list<array<string, mixed>>
     *
     * @throws OpenAiException
     */
    public function rankCombinations(array $wardrobeItems, array $preferences = []): array
    {
        $model = config('services.openai.combination_model', 'gpt-5.1');
        $schema = $this->combinationResponseSchema();

        $payload = [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => 'You are a fashion stylist. Rank outfit combinations from the user wardrobe. '
                        .'Return only valid JSON matching the provided schema. '
                        .'Each combination must use real wardrobe item IDs from the input. '
                        .'Prefer color harmony, occasion fit, and style coherence.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'wardrobe_items' => $wardrobeItems,
                        'preferences' => $preferences,
                        'instructions' => 'Return the best outfit combinations sorted by score descending.',
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'outfit_combinations',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];

        $response = Http::withToken($this->getApiKey())
            ->acceptJson()
            ->timeout(120)
            ->post($this->endpoint('/responses'), $payload);

        if (! $response->successful()) {
            throw OpenAiException::fromResponse($response->status(), $response->json(), '/responses');
        }

        $json = $this->extractStructuredJson($response->json(), '/responses');
        $combinations = $json['combinations'] ?? [];

        if (! is_array($combinations)) {
            throw new OpenAiException('OpenAI combination response missing combinations array.', '/responses');
        }

        usort($combinations, static fn (array $a, array $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return $combinations;
    }

    public function studioReferencePath(): string
    {
        return (string) config('services.openai.studio_reference_path', 'assets/images/studio_template.jpg');
    }

    public function resolveStudioReferenceAbsolutePath(): string
    {
        $relative = ltrim($this->studioReferencePath(), '/');
        $localPath = public_path($relative);

        if (! is_file($localPath)) {
            throw new RuntimeException(sprintf('OpenAI studio reference image not found: %s', $localPath));
        }

        return $localPath;
    }

    /**
     * @return array{binary: string, mime: string}
     */
    private function resolveImageContents(string $image): array
    {
        if (str_starts_with($image, 'data:')) {
            if (! preg_match('/^data:([^;]+);base64,(.+)$/', $image, $matches)) {
                throw new RuntimeException('Invalid data URI for OpenAI image input.');
            }

            $binary = base64_decode($matches[2], true);

            if ($binary === false) {
                throw new RuntimeException('Failed to decode data URI for OpenAI image input.');
            }

            return [
                'binary' => $binary,
                'mime' => $matches[1],
            ];
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            $response = Http::timeout(120)->get($image);

            if (! $response->successful()) {
                throw new RuntimeException(sprintf('Failed to download OpenAI reference image: %s', $image));
            }

            return [
                'binary' => $response->body(),
                'mime' => $this->guessMimeType($image, $response->body()),
            ];
        }

        $absolutePath = $this->resolveStoredAbsolutePath($image);

        if ($absolutePath === null) {
            throw new RuntimeException(sprintf('Image not found for OpenAI input: %s', $image));
        }

        $binary = file_get_contents($absolutePath);

        if ($binary === false) {
            throw new RuntimeException(sprintf('Failed to read OpenAI reference image: %s', $image));
        }

        return [
            'binary' => $binary,
            'mime' => $this->guessMimeType($absolutePath, $binary),
        ];
    }

    private function resolveStoredAbsolutePath(string $path): ?string
    {
        if (is_file($path)) {
            return $path;
        }

        $localPath = storage_path('app/'.$path);

        if (is_file($localPath)) {
            return $localPath;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        if (Storage::disk('s3')->exists($path)) {
            $tempPath = storage_path('app/tmp/openai/'.Str::uuid().'-'.basename($path));
            $directory = dirname($tempPath);

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($tempPath, Storage::disk('s3')->get($path));

            return $tempPath;
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

    /**
     * @throws OpenAiException
     */
    private function persistImageResponse(Response $response, string $endpoint): string
    {
        if (! $response->successful()) {
            throw OpenAiException::fromResponse($response->status(), $response->json(), $endpoint);
        }

        $payload = $response->json();
        $data = is_array($payload) ? ($payload['data'][0] ?? null) : null;

        if (! is_array($data)) {
            throw new OpenAiException('OpenAI image response did not include image data.', $endpoint);
        }

        $relativePath = 'tmp/openai/'.Str::uuid().'.jpg';
        $disk = Storage::disk('public');

        if (isset($data['b64_json']) && is_string($data['b64_json'])) {
            $binary = base64_decode($data['b64_json'], true);

            if ($binary === false) {
                throw new OpenAiException('OpenAI image response contained invalid base64 data.', $endpoint);
            }

            $disk->put($relativePath, $binary);

            return asset($relativePath);
        }

        if (isset($data['url']) && is_string($data['url']) && $data['url'] !== '') {
            $download = Http::timeout(120)->get($data['url']);

            if (! $download->successful()) {
                throw new OpenAiException('Failed to download OpenAI generated image URL.', $endpoint);
            }

            $disk->put($relativePath, $download->body());

            return asset($relativePath);
        }

        throw new OpenAiException('OpenAI image response did not include b64_json or url.', $endpoint);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     *
     * @throws OpenAiException
     */
    private function extractStructuredJson(?array $payload, string $endpoint): array
    {
        if ($payload === null) {
            throw new OpenAiException('OpenAI response payload was empty.', $endpoint);
        }

        $text = $this->extractResponseText($payload);

        if ($text === null || $text === '') {
            throw new OpenAiException('OpenAI response did not include structured text output.', $endpoint);
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new OpenAiException('OpenAI structured response was not valid JSON.', $endpoint);
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractResponseText(array $payload): ?string
    {
        if (isset($payload['output_text']) && is_string($payload['output_text'])) {
            return $payload['output_text'];
        }

        $output = $payload['output'] ?? null;

        if (! is_array($output)) {
            return null;
        }

        $parts = [];

        foreach ($output as $item) {
            if (! is_array($item)) {
                continue;
            }

            $content = $item['content'] ?? null;

            if (! is_array($content)) {
                continue;
            }

            foreach ($content as $block) {
                if (! is_array($block)) {
                    continue;
                }

                if (($block['type'] ?? null) === 'output_text' && is_string($block['text'] ?? null)) {
                    $parts[] = $block['text'];
                }

                if (($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                    $parts[] = $block['text'];
                }
            }
        }

        if ($parts === []) {
            return null;
        }

        return implode('', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    private function combinationResponseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'combinations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'top_id' => ['type' => ['integer', 'null']],
                            'bottom_id' => ['type' => ['integer', 'null']],
                            'shoes_id' => ['type' => ['integer', 'null']],
                            'dress_id' => ['type' => ['integer', 'null']],
                            'score' => ['type' => 'number'],
                            'reasoning' => ['type' => 'string'],
                        ],
                        'required' => ['top_id', 'bottom_id', 'shoes_id', 'dress_id', 'score', 'reasoning'],
                    ],
                ],
            ],
            'required' => ['combinations'],
        ];
    }

    private function getApiKey(): string
    {
        $apiKey = $this->apiKey ?? config('services.openai.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        return $apiKey;
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim($this->baseUrl ?? config('services.openai.base_url', 'https://api.openai.com/v1'), '/');

        return $baseUrl.$path;
    }
}
