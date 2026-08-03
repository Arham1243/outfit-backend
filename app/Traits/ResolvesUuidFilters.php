<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait ResolvesUuidFilters
{
    protected function resolveUuidFilters(Request $request, array $fieldModelMap): void
    {
        $filters = $request->input('filters', []);
        $changed = false;

        foreach ($filters as &$filter) {
            $field = $filter['field'] ?? null;
            $value = $filter['value'] ?? null;

            if (! $field || ! isset($fieldModelMap[$field]) || $value === null || $value === '') {
                continue;
            }

            $model = $fieldModelMap[$field];

            if (is_array($value)) {
                $resolved = [];
                foreach ($value as $item) {
                    if ($item === null || $item === '') {
                        continue;
                    }
                    $id = $this->resolveFilterId($request, $model, $item);
                    if ($id !== null) {
                        $resolved[] = $id;
                    }
                }
                if ($resolved !== []) {
                    $filter['value'] = $resolved;
                    $changed = true;
                }

                continue;
            }

            $resolved = $this->resolveFilterId($request, $model, $value);
            if ($resolved !== null && ! is_numeric($value)) {
                $filter['value'] = $resolved;
                $changed = true;
            }
        }

        if ($changed) {
            $request->merge(['filters' => $filters]);
        }
    }

    /**
     * @param  class-string  $model
     */
    private function resolveFilterId(Request $request, string $model, mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $query = $model::query()->where('uuid', $value);

        $id = $query->value('id');

        return $id !== null ? (int) $id : null;
    }
}
