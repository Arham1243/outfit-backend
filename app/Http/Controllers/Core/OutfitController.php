<?php

namespace App\Http\Controllers\Core;

use App\Models\Core\Wardrobe;
use App\Support\OutfitRequirements;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OutfitController
{
    private const DUMMY_TOTAL = 36;

    /**
     * Return paginated dummy outfit images for infinite-scroll testing.
     */
    public function index(Request $request)
    {
        return response()->json($this->paginatedOutfits($request));
    }

    /**
     * Validate profile + wardrobe, then return the first page of outfits.
     */
    public function generate(Request $request)
    {
        $user = $request->user();
        $errors = [];
        $missingWardrobeGroups = [];

        if ($user->height === null) {
            $errors['height'] = [__('outfit.height_required')];
        }

        if (empty($user->face_image)) {
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
                    'requires_settings' => isset($errors['height']) || isset($errors['face_image']),
                ],
            ], 422);
        }

        return response()->json($this->paginatedOutfits($request));
    }

    /**
     * @return array{data: array<int, array<string, string>>, meta: array<string, int>}
     */
    private function paginatedOutfits(Request $request): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, min(60, (int) $request->query('limit', 12)));
        $lastPage = (int) ceil(self::DUMMY_TOTAL / $limit);
        $page = min($page, max(1, $lastPage));

        $offset = ($page - 1) * $limit;
        $remaining = self::DUMMY_TOTAL - $offset;
        $count = min($limit, max(0, $remaining));

        $imageUrl = asset('assets/images/outfit-example.webp');
        $data = [];

        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'uuid' => (string) Str::uuid(),
                'image_url' => $imageUrl,
            ];
        }

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $limit,
                'total' => self::DUMMY_TOTAL,
            ],
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
