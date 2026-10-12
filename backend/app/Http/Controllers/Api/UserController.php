<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Menampilkan seluruh user.
     */
    public function index()
    {
        $users = User::with([
            'divisi:id,nama_divisi,divisi_alias',
            'roles:id,name',
        ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Data user berhasil diambil.',
            'data' => $users,
        ]);
    }

    /**
     * Menampilkan detail user.
     */
    public function show(string $id)
    {
        $user = User::with([
            'divisi:id,nama_divisi,divisi_alias',
            'roles:id,name',
        ])->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail user berhasil diambil.',
            'data' => $user,
        ]);
    }

    /**
     * Membuat user baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->whereNull('deleted_at'),
            ],

            'jabatan' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'sometimes',
                'boolean',
            ],

            'id_divisi' => [
                'nullable',
                'integer',
                'exists:divisis,id',
            ],

            'role' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')
                    ->where('guard_name', 'web')
                    ->whereNull('deleted_at'),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'jabatan' => $validated['jabatan'] ?? null,
            'status' => $validated['status'] ?? true,
            'id_divisi' => $validated['id_divisi'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        $role = Role::findOrFail($validated['role']);

        $user->assignRole($role);

        return response()->json([
            'message' => 'User berhasil ditambahkan.',
            'data' => $user->load([
                'divisi:id,nama_divisi,divisi_alias',
                'roles:id,name',
            ]),
        ], 201);
    }

    /**
     * Memperbarui user.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->whereNull('deleted_at')
                    ->ignore($user->id),
            ],

            'jabatan' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'sometimes',
                'boolean',
            ],

            'id_divisi' => [
                'nullable',
                'integer',
                'exists:divisis,id',
            ],

            'role' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')
                    ->where('guard_name', 'web')
                    ->whereNull('deleted_at'),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->jabatan = $validated['jabatan'] ?? null;
        $user->status = $validated['status'] ?? $user->status;
        $user->id_divisi = $validated['id_divisi'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make(
                $validated['password']
            );
        }

        $user->save();

        $role = Role::findOrFail($validated['role']);

        $user->syncRoles([$role]);

        return response()->json([
            'message' => 'User berhasil diperbarui.',
            'data' => $user->fresh()->load([
                'divisi:id,nama_divisi,divisi_alias',
                'roles:id,name',
            ]),
        ]);
    }

    /**
     * Menghapus user.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'User yang sedang login tidak dapat dihapus.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus.',
        ]);
    }
}