<?php

namespace App\Http\Controllers\Core;

use App\Http\Requests\Core\LanguageRequest;
use App\Http\Resources\Core\LanguageResource;
use App\Http\Resources\Core\LanguagePreferenceResource;
use App\Models\Core\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class LanguageController extends Controller
{
    use DisableAuthorization;

    protected $model = Language::class;

    protected $request = LanguageRequest::class;

    protected $resource = LanguageResource::class;

    public function searchableBy(): array
    {
        return ['name', 'code'];
    }

    public function sortableBy(): array
    {
        return [
            'sort_order',
            'name',
            'code',
            'region_code',
            'is_active',
            'is_rtl',
            'created_at',
        ];
    }

    public function filterableBy(): array
    {
        return ['is_active', 'is_rtl'];
    }

    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return parent::buildIndexFetchQuery($request, $requestedRelations)
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Active platform languages for user preference dropdowns.
     */
    public function activeOptions(): JsonResponse
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => LanguagePreferenceResource::collection($languages),
        ]);
    }

    public function changeStatus(Language $language)
    {
        $language->update(['is_active' => ! $language->is_active]);

        return new LanguageResource($language->fresh());
    }
}
