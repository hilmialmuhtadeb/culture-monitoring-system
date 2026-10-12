<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    // Ambil permission guard web sebagai sumber data halaman pengelolaan permission.
    public function index(Request $request)
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Data permission berhasil diambil.',
            'data' => $permissions,
        ]);
    }

    // Tampilkan satu permission berdasarkan ID jika masih terdaftar pada guard web.
    public function show(string $id)
    {
        $permission = Permission::where('guard_name', 'web')
            ->find($id);

        if (!$permission) {
            return response()->json([
                'message' => 'Permission tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail permission berhasil diambil.',
            'data' => $permission,
        ]);
    }

    // Validasi dan buat permission baru yang dapat diberikan kepada role.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')
                    ->where('guard_name', 'web')
                    ->whereNull('deleted_at'),
            ],
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return response()->json([
            'message' => 'Permission berhasil ditambahkan.',
            'data' => $permission,
        ], 201);
    }

    // Perbarui nama permission tanpa mengubah relasi permission dengan role.
    public function update(Request $request, string $id)
    {
        $permission = Permission::where('guard_name', 'web')
            ->find($id);

        if (!$permission) {
            return response()->json([
                'message' => 'Permission tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')
                    ->where('guard_name', 'web')
                    ->whereNull('deleted_at')
                    ->ignore($permission->id),
            ],
        ]);

        $permission->update([
            'name' => $validated['name'],
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)
            ->forgetCachedPermissions();

        return response()->json([
            'message' => 'Permission berhasil diperbarui.',
            'data' => $permission->fresh(),
        ]);
    }

    // Hapus permission dan bersihkan cache Spatie agar hak akses terbaru langsung digunakan.
    public function destroy(string $id)
    {
        $permission = Permission::where('guard_name', 'web')
            ->find($id);

        if (!$permission) {
            return response()->json([
                'message' => 'Permission tidak ditemukan.',
            ], 404);
        }

        $permission->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)
            ->forgetCachedPermissions();

        return response()->json([
            'message' => 'Permission berhasil dihapus.',
        ]);
    }
}