<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Data role berhasil diambil.',
            'data' => $roles,
        ]);
    }

    public function show(string $id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail role berhasil diambil.',
            'data' => $role,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->whereNull('deleted_at'),
            ],
            'permissions' => [
                'required',
                'array',
            ],
            'permissions.*' => [
                'integer',
                Rule::exists('permissions', 'id')
                    ->where('guard_name', 'web')
                    ->whereNull('deleted_at'),
            ],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $permissions = Permission::where('guard_name', 'web')
            ->whereIn('id', $validated['permissions'])
            ->get();

        $role->syncPermissions($permissions);

        return response()->json([
            'message' => 'Role berhasil ditambahkan.',
            'data' => $role->load('permissions'),
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->whereNull('deleted_at')
                    ->ignore($role->id),
            ],
            'permissions' => [
                'required',
                'array',
            ],
            'permissions.*' => [
                'integer',
                Rule::exists('permissions', 'id')
                    ->where('guard_name', 'web')
                    ->whereNull('deleted_at'),
            ],
        ]);

        $role->update([
            'name' => $validated['name'],
        ]);

        $permissions = Permission::where('guard_name', 'web')
            ->whereIn('id', $validated['permissions'])
            ->get();

        $role->syncPermissions($permissions);

        return response()->json([
            'message' => 'Role berhasil diperbarui.',
            'data' => $role->fresh()->load('permissions'),
        ]);
    }

    public function destroy(string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Role tidak dapat dihapus karena masih digunakan oleh user.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role berhasil dihapus.',
        ]);
    }
}