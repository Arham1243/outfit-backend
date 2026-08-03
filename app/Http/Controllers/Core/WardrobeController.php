<?php

namespace App\Http\Controllers\Core;

use App\Http\Requests\Core\WardrobeRequest;
use App\Http\Resources\Core\WardrobeResource;
use App\Models\Core\Wardrobe;
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
        return ['uuid', 'type'];
    }

    public function sortableBy(): array
    {
        return ['type', 'created_at', 'updated_at'];
    }

    public function filterableBy(): array
    {
        return ['uuid', 'type'];
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
        $this->handleFile($request, $wardrobe, 'image', 'wardrobes/images');
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
}
