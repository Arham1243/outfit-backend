<?php

namespace App\Jobs;

use App\Models\Core\Wardrobe;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ClassifyWardrobeItem implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const LABELS = [
        't-shirt',
        'shirt',
        'pants',
        'jeans',
        'shoes',
        'jacket',
        'hoodie',
        'sweatshirt',
        'shorts',
    ];

    private const LABEL_KEYWORDS = [
        't-shirt' => ['t-shirt', 'tee shirt', 'jersey'],
        'shirt' => [' dress shirt', ' polo', ' shirt', ' blouse', ' tank top'],
        'pants' => [' trouser', ' pants', ' slacks', ' chino'],
        'jeans' => [' jean', ' denim'],
        'shoes' => [' shoe', ' sneaker', ' boot', ' sandal', ' slipper', ' loafer', ' clog', ' mule'],
        'jacket' => [' jacket', ' coat', ' blazer', ' parka', ' windbreaker', ' anorak'],
        'hoodie' => [' hoodie', ' hooded sweatshirt'],
        'sweatshirt' => [' sweatshirt', ' crewneck', ' crew neck', ' sweater', ' cardigan', ' pullover', ' fleece'],
        'shorts' => [' shorts', ' trunks'],
    ];

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(public Wardrobe $wardrobe) {}

    public function handle(): void
    {
        $token = config('services.huggingface.token');

        if (empty($token)) {
            Log::warning('ClassifyWardrobeItem skipped: HF_API_TOKEN is not configured.', [
                'wardrobe_uuid' => $this->wardrobe->uuid,
            ]);

            return;
        }

        if (empty($this->wardrobe->image)) {
            Log::warning('ClassifyWardrobeItem skipped: wardrobe has no image.', [
                'wardrobe_uuid' => $this->wardrobe->uuid,
            ]);

            return;
        }

        $imageContents = $this->readImageContents($this->wardrobe->image);

        if ($imageContents === null) {
            Log::error('ClassifyWardrobeItem failed: image not found on storage.', [
                'wardrobe_uuid' => $this->wardrobe->uuid,
                'image_path' => $this->wardrobe->image,
            ]);

            return;
        }

        $predictions = $this->classifyImage($imageContents);

        if ($predictions === null) {
            return;
        }

        $topPrediction = $predictions[0];

        $this->wardrobe->refresh();

        if (! empty($this->wardrobe->type)) {
            return;
        }

        $this->wardrobe->update([
            'type' => $topPrediction['label'],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('ClassifyWardrobeItem failed after all retries.', [
            'wardrobe_uuid' => $this->wardrobe->uuid,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function readImageContents(string $path): ?string
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->get($path);
        }

        if (Storage::disk('s3')->exists($path)) {
            return Storage::disk('s3')->get($path);
        }

        return null;
    }

    /**
     * @return list<array{label: string, score: float}>|null
     */
    private function classifyImage(string $imageContents): ?array
    {
        $url = config('services.huggingface.image_classification_url');

        $response = Http::withToken(config('services.huggingface.token'))
            ->acceptJson()
            ->timeout(60)
            ->post($url, [
                'inputs' => base64_encode($imageContents),
            ]);

        if ($response->status() === 503) {
            $estimatedTime = (int) ($response->json('estimated_time') ?? 30);
            $this->release(max($estimatedTime, 10));

            return null;
        }

        if ($response->status() === 403) {
            $message = 'HF_API_TOKEN lacks Inference Providers permission. Create a fine-grained token at https://huggingface.co/settings/tokens with "Make calls to Inference Providers" enabled, then update .env and run php artisan config:clear.';
            Log::error($message, [
                'wardrobe_uuid' => $this->wardrobe->uuid,
                'response' => $response->body(),
            ]);
            $this->fail(new \RuntimeException($message));

            return null;
        }

        if ($response->status() === 400) {
            $message = 'The configured Hugging Face model is not supported on the free hf-inference provider. Update HF_IMAGE_CLASSIFICATION_URL to a supported model such as google/vit-base-patch16-224.';
            Log::error($message, [
                'wardrobe_uuid' => $this->wardrobe->uuid,
                'response' => $response->body(),
            ]);
            $this->fail(new \RuntimeException($message));

            return null;
        }

        if (! $response->successful()) {
            throw new \RuntimeException(sprintf(
                'Hugging Face API request failed with status %d: %s',
                $response->status(),
                $response->body()
            ));
        }

        $rawPredictions = $response->json();

        if (! is_array($rawPredictions) || empty($rawPredictions)) {
            throw new \RuntimeException('Hugging Face API returned an empty or invalid response.');
        }

        return $this->mapPredictionsToCategories($rawPredictions);
    }

    /**
     * @param  list<array{label: string, score: float|int}>  $rawPredictions
     * @return list<array{label: string, score: float}>
     */
    private function mapPredictionsToCategories(array $rawPredictions): array
    {
        $aggregated = [];

        foreach ($rawPredictions as $prediction) {
            $category = $this->mapLabelToCategory((string) ($prediction['label'] ?? ''));

            if ($category === null) {
                continue;
            }

            $aggregated[$category] = ($aggregated[$category] ?? 0) + (float) ($prediction['score'] ?? 0);
        }

        if ($aggregated === []) {
            throw new \RuntimeException('Could not map Hugging Face predictions to wardrobe categories.');
        }

        arsort($aggregated);

        $predictions = [];

        foreach ($aggregated as $label => $score) {
            $predictions[] = [
                'label' => $label,
                'score' => round($score, 4),
            ];
        }

        return $predictions;
    }

    private function mapLabelToCategory(string $label): ?string
    {
        $normalizedLabel = ' '.strtolower($label).' ';

        foreach (self::LABEL_KEYWORDS as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalizedLabel, $keyword)) {
                    return $category;
                }
            }
        }

        return null;
    }
}
