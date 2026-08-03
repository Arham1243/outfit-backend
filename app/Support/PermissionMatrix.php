<?php

namespace App\Support;

final class PermissionMatrix
{
    private const ALL_ACTIONS = ['view', 'create', 'edit', 'delete'];

    /** Actions that require view when enabled. */
    private const IMPLY_VIEW = ['create', 'edit', 'delete'];

    /**
     * @return array<int, string>
     */
    public static function entityKeys(): array
    {
        return array_keys(config('permission_matrix.entities', []));
    }

    public static function applicableActionsFor(string $entity): array
    {
        $entities = config('permission_matrix.entities', []);
        $default = config('permission_matrix.default_actions', self::ALL_ACTIONS);

        if (! isset($entities[$entity])) {
            return $default;
        }

        return $entities[$entity]['actions'] ?? $default;
    }

    public static function isExcludedEntity(string $entity): bool
    {
        return in_array($entity, config('permission_matrix.excluded_entities', []), true);
    }

    public static function isApplicablePermissionName(string $permissionName): bool
    {
        $parts = explode('.', $permissionName);
        $action = array_pop($parts);
        $entity = implode('.', $parts);
        $entities = config('permission_matrix.entities', []);

        if (! isset($entities[$entity])) {
            return false;
        }

        if (self::isExcludedEntity($entity)) {
            return false;
        }

        return in_array($action, self::applicableActionsFor($entity), true);
    }

    /**
     * @param  array<string, array<string, bool|null>>  $matrix
     * @return array<string, array<string, bool|null>>
     */
    public static function normalizeRoleMatrix(array $matrix): array
    {
        foreach ($matrix as $entity => $actions) {
            if (self::isExcludedEntity($entity)) {
                unset($matrix[$entity]);
                continue;
            }

            foreach (self::ALL_ACTIONS as $action) {
                if (! in_array($action, self::applicableActionsFor($entity), true)) {
                    $matrix[$entity][$action] = null;
                }
            }

            $matrix[$entity] = self::applyDependenciesForEntity(
                $entity,
                $matrix[$entity]
            );
        }

        return $matrix;
    }

    /**
     * @param  array<string, bool|null>  $actions
     * @return array<string, bool|null>
     */
    public static function applyDependenciesForEntity(string $entity, array $actions): array
    {
        $applicable = self::applicableActionsFor($entity);

        $requiresView = false;
        foreach (self::IMPLY_VIEW as $action) {
            if (
                in_array($action, $applicable, true)
                && ($actions[$action] ?? null) === true
            ) {
                $requiresView = true;
                break;
            }
        }

        if ($requiresView && in_array('view', $applicable, true)) {
            $actions['view'] = true;
        }

        if (
            in_array('view', $applicable, true)
            && ($actions['view'] ?? null) === false
        ) {
            foreach (self::IMPLY_VIEW as $action) {
                if (in_array($action, $applicable, true)) {
                    $actions[$action] = false;
                }
            }
        }

        return $actions;
    }

    /**
     * @param  array<int, string>  $permissionNames
     * @return array<int, string>
     */
    public static function expandPermissionNames(array $permissionNames): array
    {
        $expanded = [];

        foreach ($permissionNames as $name) {
            if (! is_string($name) || $name === '') {
                continue;
            }

            $expanded[] = $name;

            $parts = explode('.', $name);
            $action = array_pop($parts);
            if (! in_array($action, self::IMPLY_VIEW, true)) {
                continue;
            }

            $entity = implode('.', $parts);
            $viewName = "{$entity}.view";

            if (
                self::isApplicablePermissionName($viewName)
                && ! in_array($viewName, $expanded, true)
            ) {
                $expanded[] = $viewName;
            }
        }

        return array_values(array_unique($expanded));
    }
}
