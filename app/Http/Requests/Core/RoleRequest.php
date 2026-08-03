<?php

namespace App\Http\Requests\Core;

use App\Http\Requests\Concerns\ResolvesOrionRouteModel;
use App\Models\Core\Role;
use App\Models\User;
use Orion\Http\Requests\Request;

class RoleRequest extends Request
{
    use ResolvesOrionRouteModel;

    public function commonRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => [
                'required',
                'boolean',
                function ($attribute, $value, $fail) {
                    $role = $this->routeModel('role', Role::class);
                    if ($role && $role->is_system && $value === false) {
                        $fail('System roles cannot be deactivated.');
                    }

                    if ($role && $value === false) {
                        $isAssigned = User::query()
                            ->where('role_id', $role->id)
                            ->exists();

                        if ($isAssigned) {
                            $fail(trans('messages.role_assigned_cannot_deactivate'));
                        }
                    }
                },
            ],
        ];
    }
}
