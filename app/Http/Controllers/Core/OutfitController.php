<?php

namespace App\Http\Controllers\Core;

use App\Jobs\GenerateOutfitJob;
use App\Models\Core\GeneratedOutfit;
use App\Models\Core\Wardrobe;
use App\Services\OutfitCombinationService;
use App\Support\FaceMode;
use App\Support\OutfitRequirements;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OutfitController
{
    public function __construct(
        private readonly OutfitCombinationService $combinationService,
    ) {}

    /**
     * Return paginated completed outfit images for infinite scroll.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, min(60, (int) $request->query('limit', 12)));

        $query = GeneratedOutfit::query()
            ->where('user_id', $user->id)
            ->latest('created_at');

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (GeneratedOutfit $outfit) => $this->serializeOutfit($outfit))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return wardrobe combination totals and remaining generate capacity.
     */
    public function combinationStats(Request $request)
    {
        return response()->json([
            'data' => $this->combinationService->statsForUser($request->user()),
        ]);
    }

    /**
     * Validate profile + wardrobe, enqueue generation jobs, return pending batch.
     */
    public function generate(Request $request)
    {
        $user = $request->user();
        $errors = [];
        $missingWardrobeGroups = [];

        if ($user->height === null) {
            $errors['height'] = [__('outfit.height_required')];
        }

        if (! in_array($user->gender, ['male', 'female'], true)) {
            $errors['gender'] = [__('outfit.gender_required')];
        }

        if (FaceMode::requiresFaceImage($user->faceMode()) && empty($user->face_image)) {
            $errors['face_image'] = [__('outfit.face_image_required')];
        }

        $typeCounts = $this->wardrobeTypeCountsForUser($user->id);
        $missingWardrobeGroups = OutfitRequirements::missingGroups($typeCounts);

        if ($missingWardrobeGroups !== []) {
            $groupLabels = array_map(
                fn (string $group) => __('outfit.groups.'.$group),
                $missingWardrobeGroups
            );

            $errors['wardrobe'] = [
                __('outfit.wardrobe_incomplete', [
                    'groups' => implode(', ', $groupLabels),
                ]),
            ];
        }

        if ($errors !== []) {
            return response()->json([
                'message' => __('outfit.generate_failed'),
                'errors' => $errors,
                'meta' => [
                    'missing_wardrobe_groups' => $missingWardrobeGroups,
                    'requires_settings' => isset($errors['height'])
                        || isset($errors['gender'])
                        || isset($errors['face_image']),
                ],
            ], 422);
        }

        $combinations = $this->combinationService->generateForUser($user);

        if ($combinations === []) {
            $latestBatchId = GeneratedOutfit::query()
                ->where('user_id', $user->id)
                ->latest('created_at')
                ->value('batch_id');

            return response()->json([
                'message' => __('outfit.no_combinations_available'),
                'errors' => [
                    'wardrobe' => [__('outfit.no_combinations_available')],
                ],
                'meta' => array_merge(
                    $this->combinationService->statsForUser($user),
                    [
                        'all_combinations_exhausted' => true,
                        'latest_batch_id' => $latestBatchId,
                    ]
                ),
            ], 422);
        }

        $batchId = (string) Str::uuid();
        $outfits = [];

        foreach ($combinations as $combination) {
            $outfit = GeneratedOutfit::query()->create([
                'user_id' => $user->id,
                'batch_id' => $batchId,
                'wardrobe_ids' => $combination['wardrobe_ids'],
                'status' => GeneratedOutfit::STATUS_PENDING,
            ]);

            GenerateOutfitJob::dispatch($outfit);
            $outfits[] = $outfit;
        }

        $total = count($outfits);

        return response()->json([
            'data' => collect($outfits)->map(fn (GeneratedOutfit $outfit) => $this->serializeOutfit($outfit))->values(),
            'meta' => array_merge(
                $this->combinationService->statsForUser($user),
                [
                    'batch_id' => $batchId,
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $total,
                    'total' => $total,
                ]
            ),
        ]);
    }

    /**
     * Poll current status for all outfits in a generation batch.
     */
    public function showBatch(Request $request, string $batchId)
    {
        return response()->json($this->batchPayload($request->user()->id, $batchId));
    }

    /**
     * Return the user's most recent generation batch (any status).
     */
    public function latestBatch(Request $request)
    {
        $batchId = GeneratedOutfit::query()
            ->where('user_id', $request->user()->id)
            ->latest('created_at')
            ->value('batch_id');

        if (! $batchId) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'batch_id' => null,
                    'total' => 0,
                    'completed' => 0,
                    'failed' => 0,
                    'pending' => 0,
                ],
            ]);
        }

        return response()->json($this->batchPayload($request->user()->id, $batchId));
    }

    /**
     * @return array{data: \Illuminate\Support\Collection, meta: array<string, mixed>}|never
     */
    private function batchPayload(int $userId, string $batchId): array
    {
        $outfits = GeneratedOutfit::query()
            ->where('user_id', $userId)
            ->where('batch_id', $batchId)
            ->orderBy('id')
            ->get();

        if ($outfits->isEmpty()) {
            abort(404, __('outfit.batch_not_found'));
        }

        return [
            'data' => $outfits->map(fn (GeneratedOutfit $outfit) => $this->serializeOutfit($outfit))->values(),
            'meta' => [
                'batch_id' => $batchId,
                'total' => $outfits->count(),
                'completed' => $outfits->where('status', GeneratedOutfit::STATUS_COMPLETED)->count(),
                'failed' => $outfits->where('status', GeneratedOutfit::STATUS_FAILED)->count(),
                'pending' => $outfits->whereIn('status', [
                    GeneratedOutfit::STATUS_PENDING,
                    GeneratedOutfit::STATUS_PROCESSING,
                ])->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOutfit(GeneratedOutfit $outfit): array
    {
        return [
            'uuid' => $outfit->uuid,
            'status' => $outfit->status,
            'image_url' => $outfit->image_url,
            'wardrobe_ids' => $outfit->wardrobe_ids,
            'generation_provider' => $outfit->generation_provider,
            'generation_model' => $outfit->generation_model,
            'generation_settings' => $outfit->generation_settings,
            'error' => $outfit->error,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function wardrobeTypeCountsForUser(int $userId): array
    {
        $rows = Wardrobe::query()
            ->where('user_id', $userId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get();

        $data = [];

        foreach ($rows as $row) {
            if ($row->type) {
                $data[$row->type] = (int) $row->count;
            }
        }

        return $data;
    }
}
