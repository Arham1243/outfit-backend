<?php

namespace App\Http\Controllers\Core;

use App\Http\Requests\Core\RoleRequest;
use App\Http\Resources\Core\RoleResource;
use App\Models\Core\Role;
use App\Models\User;
use App\Support\Core\SystemRecordGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request as OrionRequest;

class RoleController extends Controller
{
    use DisableAuthorization;

    protected $model = Role::class;

    protected $request = RoleRequest::class;

    protected $resource = RoleResource::class;

    protected function keyName(): string
    {
        return 'uuid';
    }


    public function searchableBy(): array
    {
        return ['name'];
    }

    public function sortableBy(): array
    {
        return ['name', 'status', 'created_at'];
    }

    public function filterableBy(): array
    {
        return ['status', 'name'];
    }

    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        $query
            ->withCount([
                'assignedUsers as assigned_users_count',
            ]);

        return $query;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Role  $role
     * @return void
     */
    protected function beforeSave(OrionRequest $request, Model $entity)
    {
        if (empty($entity->uuid)) {
            $entity->uuid = \Illuminate\Support\Str::uuid();
        }
    }

    protected function beforeUpdate(OrionRequest $request, Model $entity)
    {
        SystemRecordGuard::throwIfSystem($entity, 'System roles cannot be updated.');
    }

    protected function beforeDestroy(OrionRequest $request, Model $entity)
    {
        SystemRecordGuard::throwIfSystem($entity, 'System roles cannot be deleted.');
    }

    public function changeStatus(Request $request, Role $role)
    {
        SystemRecordGuard::throwIfSystem(
            $role,
            'System roles cannot be activated or deactivated.'
        );

        if ($role->status) {
            $isAssigned = User::query()
                ->where('role_id', $role->id)
                ->exists();

            if ($isAssigned) {
                throw ValidationException::withMessages([
                    'role' => trans('messages.role_assigned_cannot_deactivate'),
                ]);
            }
        }

        $role->update(['status' => ! $role->status]);

        return new RoleResource($role->fresh());
    }

    public function deleteRole(Request $request, Role $role)
    {
        SystemRecordGuard::throwIfSystem($role, 'System roles cannot be deleted.');

        $isAssigned = User::query()
            ->where('role_id', $role->id)
            ->exists();

        if ($isAssigned) {
            throw ValidationException::withMessages([
                'role' => 'This role is assigned to one or more users and cannot be deleted.',
            ]);
        }

        $role->delete();

        return response()->noContent();
    }
}
