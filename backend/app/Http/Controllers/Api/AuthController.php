<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Login user
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::with('divisi')->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // Hapus token lama agar tidak menumpuk
        $user->tokens()->delete();

        // Buat token baru
        $token = $user->createToken('vue-frontend')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'permissions' => $user->getAllPermissions()
                    ->pluck('name')
                    ->values(),
                'divisi' => $user->divisi ? [
                    'id' => $user->divisi->id,
                    'nama' => $user->divisi->nama_divisi,
                    'alias' => $user->divisi->divisi_alias,
                ] : null,
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Mendapatkan data user yang sedang login
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('divisi');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'permissions' => $user->getAllPermissions()
                    ->pluck('name')
                    ->values(),
                'divisi' => $user->divisi ? [
                    'id' => $user->divisi->id,
                    'nama' => $user->divisi->nama_divisi,
                    'alias' => $user->divisi->divisi_alias,
                ] : null,
            ],
        ]);
    }
}