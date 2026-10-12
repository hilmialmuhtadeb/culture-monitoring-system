<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Divisi;
use Illuminate\Http\Request;

class DivisiController extends Controller
{
    /**
     * Menampilkan semua divisi.
     */
    public function index()
    {
        $divisi = Divisi::orderBy('nama_divisi')->get();

        return response()->json([
            'message' => 'Data divisi berhasil diambil.',
            'data' => $divisi,
        ]);
    }

    /**
     * Menampilkan detail divisi.
     */
    public function show(string $id)
    {
        $divisi = Divisi::find($id);

        if (!$divisi) {
            return response()->json([
                'message' => 'Divisi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail divisi berhasil diambil.',
            'data' => $divisi,
        ]);
    }

    /**
     * Menambahkan divisi baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_divisi' => [
                'required',
                'string',
                'max:255',
            ],
            'divisi_alias' => [
                'nullable',
                'string',
                'max:100',
            ],
            'status' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $validated['status'] = $validated['status'] ?? true;

        $divisi = Divisi::create($validated);

        return response()->json([
            'message' => 'Divisi berhasil ditambahkan.',
            'data' => $divisi,
        ], 201);
    }

    /**
     * Memperbarui divisi.
     */
    public function update(Request $request, string $id)
    {
        $divisi = Divisi::find($id);

        if (!$divisi) {
            return response()->json([
                'message' => 'Divisi tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'nama_divisi' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'divisi_alias' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'status' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $divisi->update($validated);

        return response()->json([
            'message' => 'Divisi berhasil diperbarui.',
            'data' => $divisi->fresh(),
        ]);
    }

    /**
     * Menghapus divisi.
     */
    public function destroy(string $id)
    {
        $divisi = Divisi::find($id);

        if (!$divisi) {
            return response()->json([
                'message' => 'Divisi tidak ditemukan.',
            ], 404);
        }

        if ($divisi->users()->exists()) {
            return response()->json([
                'message' => 'Divisi tidak dapat dihapus karena masih digunakan oleh user.',
            ], 422);
        }

        $divisi->delete();

        return response()->json([
            'message' => 'Divisi berhasil dihapus.',
        ]);
    }
}