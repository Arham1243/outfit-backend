<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $all = Permission::all()->pluck('name');

        $matrix = [];

        foreach ($all as $perm) {
            $parts = explode('.', $perm);
            $action = array_pop($parts);
            $entity = implode('.', $parts);

            if (!isset($matrix[$entity])) {
                $matrix[$entity] = [
                    'view' => null,
                    'create' => null,
                    'edit' => null,
                    'delete' => null,
                ];
            }

            $matrix[$entity][$action] = true;
        }

        return response()->json($matrix);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json($user->getAllPermissions()->pluck('name'));
    }
}
