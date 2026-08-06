<?php

namespace App\Jobs;

use App\Contracts\OutfitGeneration\OutfitGenerationProvider;
use App\Models\Core\GeneratedOutfit;
use App\Models\Core\Wardrobe;
use App\Models\User;
use App\Services\OutfitGeneration\OutfitGenerationException;
use App\Support\OutfitRequirements;
use App\Support\UserUploadPath;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateOutfitJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public GeneratedOutfit $generatedOutfit) {}

    public function handle(OutfitGenerationProvider $outfitGenerationProvider): void
    {
        $generatedOutfit = $this->generatedOutfit->fresh();

        if (! $generatedOutfit || $generatedOutfit->status === GeneratedOutfit::STATUS_COMPLETED) {
            return;
        }

        $generatedOutfit->update([
            'status' => GeneratedOutfit::STATUS_PROCESSING,
            'error' => null,
        ]);

        try {
            $user = User::query()->findOrFail($generatedOutfit->user_id);
            $baseModelImage = $this->resolveBaseModelImage($user, $outfitGenerationProvider);
            $wardrobeItems = $this->loadOrderedWardrobeItems($generatedOutfit);

            $modelImage = $baseModelImage;

            foreach ($wardrobeItems as $wardrobeItem) {
                $modelImage = $outfitGenerationProvider->applyGarment(
                    $modelImage,
                    (string) $wardrobeItem->image
                );
            }

            $relativePath = UserUploadPath::generatedOutfit(
                (string) $user->uuid,
                (string) $generatedOutfit->uuid
            );
            $this->persistRemoteImage($modelImage, $relativePath);

            $generatedOutfit->update([
                'status' => GeneratedOutfit::STATUS_COMPLETED,
                'image' => $relativePath,
                'error' => null,
            ]);
        } catch (OutfitGenerationException $exception) {
            $this->markFailed($generatedOutfit, $exception->getMessage(), $exception->getContext());
        } catch (Throwable $exception) {
            $this->markFailed($generatedOutfit, $exception->getMessage());
        }
    }

    public function failed(?Throwable $exception): void
    {
        $generatedOutfit = $this->generatedOutfit->fresh();

        if (! $generatedOutfit || $generatedOutfit->status === GeneratedOutfit::STATUS_COMPLETED) {
            return;
        }

        $this->markFailed($generatedOutfit, $exception?->getMessage() ?? 'GenerateOutfitJob failed.');
    }

    private function resolveBaseModelImage(User $user, OutfitGenerationProvider $outfitGenerationProvider): string
    {
        return Cache::lock('outfit-base-model:'.$user->id, 600)->block(120, function () use ($user, $outfitGenerationProvider) {
            $user->refresh();

            if ($user->hasValidBaseModelCache()) {
                return (string) $user->base_model_image;
            }

            $remoteUrl = $outfitGenerationProvider->createBaseModel(
                $user->height !== null ? (int) $user->height : null,
                is_string($user->gender) ? $user->gender : null
            );

            $relativePath = UserUploadPath::baseModel((string) $user->uuid);
            $this->persistRemoteImage($remoteUrl, $relativePath);

            $user->update([
                'base_model_image' => $relativePath,
                'base_model_fingerprint' => $user->baseModelFingerprint(),
            ]);

            return $relativePath;
        });
    }

    /**
     * @return list<Wardrobe>
     */
    private function loadOrderedWardrobeItems(GeneratedOutfit $generatedOutfit): array
    {
        $ids = $generatedOutfit->wardrobe_ids ?? [];

        if (! is_array($ids) || $ids === []) {
            throw new OutfitGenerationException('Generated outfit is missing wardrobe items.');
        }

        $items = Wardrobe::query()
            ->where('user_id', $generatedOutfit->user_id)
            ->whereIn('id', $ids)
            ->get();

        if ($items->count() !== count($ids)) {
            throw new OutfitGenerationException('One or more wardrobe items could not be found for this outfit.');
        }

        return $this->orderForTryOn($items);
    }

    /**
     * @param  Collection<int, Wardrobe>  $items
     * @return list<Wardrobe>
     */
    private function orderForTryOn(Collection $items): array
    {
        $ordered = [];

        $onePiece = $items->first(fn (Wardrobe $item) => in_array($item->type, OutfitRequirements::ONE_PIECE, true));
        $top = $items->first(fn (Wardrobe $item) => in_array($item->type, OutfitRequirements::TOPS, true));
        $bottom = $items->first(fn (Wardrobe $item) => in_array($item->type, OutfitRequirements::BOTTOMS, true));
        $shoes = $items->first(fn (Wardrobe $item) => in_array($item->type, OutfitRequirements::FOOTWEAR, true));

        if ($onePiece) {
            $ordered[] = $onePiece;
        } elseif ($top) {
            $ordered[] = $top;

            if ($bottom) {
                $ordered[] = $bottom;
            }
        }

        if ($shoes) {
            $ordered[] = $shoes;
        }

        if ($ordered === []) {
            throw new OutfitGenerationException('Could not determine try-on order for wardrobe items.');
        }

        return $ordered;
    }

    private function persistRemoteImage(string $url, string $relativePath): void
    {
        $response = Http::timeout(120)->get($url);

        if (! $response->successful()) {
            throw new OutfitGenerationException(sprintf(
                'Failed to download generated image (HTTP %d).',
                $response->status()
            ));
        }

        Storage::disk('public')->put($relativePath, $response->body());
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function markFailed(GeneratedOutfit $generatedOutfit, string $message, array $context = []): void
    {
        Log::error('GenerateOutfitJob failed.', array_merge([
            'generated_outfit_uuid' => $generatedOutfit->uuid,
            'batch_id' => $generatedOutfit->batch_id,
            'user_id' => $generatedOutfit->user_id,
            'error' => $message,
        ], $context));

        $generatedOutfit->update([
            'status' => GeneratedOutfit::STATUS_FAILED,
            'error' => $message,
        ]);
    }
}
