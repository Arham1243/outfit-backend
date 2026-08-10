<?php

namespace App\Http\Controllers\Core;

use App\Http\Requests\Core\WardrobeRequest;
use App\Http\Resources\Core\WardrobeResource;
use App\Jobs\ClassifyWardrobeItem;
use App\Models\Core\Wardrobe;
use App\Support\UserUploadPath;
use App\Traits\HandlesFiles;
use Illuminate\Http\Request;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class WardrobeController extends Controller
{
    use DisableAuthorization, HandlesFiles;

    protected $model = Wardrobe::class;

    protected $request = WardrobeRequest::class;

    protected $resource = WardrobeResource::class;

    protected function keyName(): string
    {
        return 'uuid';
    }

    public function searchableBy(): array
    {
        return ['uuid', 'type', 'name'];
    }

    public function sortableBy(): array
    {
        return ['type', 'name', 'created_at', 'updated_at'];
    }

    public function filterableBy(): array
    {
        return ['uuid', 'type', 'name'];
    }

    /**
     * Builds Eloquent query for fetching entity(-ies).
     */
    protected function buildFetchQuery(Request $request, array $requestedRelations): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::buildFetchQuery($request, $requestedRelations);

        $query->where('user_id', $request->user()->id);

        return $query;
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     */
    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        $query
            ->orderByDesc('created_at');

        return $query;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Core\Wardrobe  $wardrobe
     * @return void
     */
    protected function beforeSave($request, $wardrobe)
    {
        if (! $wardrobe->exists) {
            $wardrobe->user_id = $request->user()->id;
        }

        $this->handleFile(
            $request,
            $wardrobe,
            'image',
            UserUploadPath::wardrobeDir((string) $request->user()->uuid)
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Core\Wardrobe  $wardrobe
     * @return void
     */
    protected function afterSave($request, $wardrobe)
    {
        if ($wardrobe->image && ($wardrobe->wasRecentlyCreated || $wardrobe->wasChanged('image'))) {
            ClassifyWardrobeItem::dispatch($wardrobe);
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Core\Wardrobe  $wardrobe
     * @return void
     */
    protected function afterDestroy($request, $wardrobe)
    {
        $this->deleteFile($wardrobe->image);
    }

    /**
     * Wardrobe item counts grouped by type for the authenticated user.
     */
    public function typeCounts(Request $request)
    {
        $rows = Wardrobe::query()
            ->where('user_id', $request->user()->id)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get();

        $data = [];
        $total = 0;

        foreach ($rows as $row) {
            $key = $row->type ?: 'uncategorized';
            $data[$key] = (int) $row->count;
            $total += (int) $row->count;
        }

        return response()->json([
            'data' => $data,
            'total' => $total,
        ]);
    }

    /**
     * Bulk delete wardrobe images owned by the authenticated user.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'uuids' => ['required', 'array', 'min:1'],
            'uuids.*' => ['required', 'uuid', 'distinct'],
        ]);

        $wardrobes = Wardrobe::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('uuid', $validated['uuids'])
            ->get();

        foreach ($wardrobes as $wardrobe) {
            $image = $wardrobe->image;
            $wardrobe->delete();
            $this->deleteFile($image);
        }

        return response()->json([
            'message' => 'Wardrobe images deleted successfully',
            'deleted' => $wardrobes->count(),
        ]);
    }
}
