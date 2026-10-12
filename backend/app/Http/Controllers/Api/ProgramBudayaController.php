<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgramBudaya;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProgramBudayaController extends Controller
{
    /**
     * Display a listing of Program Budaya.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ProgramBudaya::with('creator:id,name')
            ->orderByDesc('tahun')
            ->orderByDesc('id');

        if (!$user->can('view inactive program budaya')) {
            $query->where('status', 'Aktif');
        }

        return response()->json([
            'message' => 'Data Program Budaya berhasil diambil.',
            'data' => $query->get(),
        ]);
    }

    /**
     * Store a newly created Program Budaya.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'frekuensi' => ['required', 'integer', 'min:1'],
            'tahun' => [
                'required',
                'integer',
                'min:2000',
                'max:' . (date('Y') + 1),
            ],
            'approval_document' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
        ]);

        /*
         * Status awal selalu Belum Aktif.
         *
         * Status tidak boleh ditentukan dari request
         * agar user tidak bisa membuat program langsung
         * menjadi Aktif.
         */
        $validated['status'] = 'Belum Aktif';

        /*
         * Simpan PDF approval ke:
         * storage/app/public/approval-program-budaya
         */
        $validated['approval_document'] = $request
            ->file('approval_document')
            ->store('approval-program-budaya', 'public');

        $validated['created_by'] = $request->user()->id;

        $programBudaya = ProgramBudaya::create($validated);

        return response()->json([
            'message' => 'Program Budaya berhasil ditambahkan.',
            'data' => $programBudaya->load('creator:id,name'),
        ], 201);
    }

    /**
     * Display the specified Program Budaya.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $query = ProgramBudaya::with('creator:id,name')
            ->where('id', $id);

        if (!$user->can('view inactive program budaya')) {
            $query->where('status', 'Aktif');
        }

        $programBudaya = $query->first();

        if (!$programBudaya) {
            return response()->json([
                'message' => 'Program Budaya tidak ditemukan atau tidak dapat diakses.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail Program Budaya berhasil diambil.',
            'data' => $programBudaya,
        ]);
    }

    /**
     * Update the specified Program Budaya.
     */
    public function update(Request $request, string $id)
    {
        $programBudaya = ProgramBudaya::find($id);

        if (!$programBudaya) {
            return response()->json([
                'message' => 'Program Budaya tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi' => ['sometimes', 'required', 'string'],
            'frekuensi' => ['sometimes', 'required', 'integer', 'min:1'],
            'tahun' => [
                'sometimes',
                'required',
                'integer',
                'min:2000',
                'max:' . (date('Y') + 1),
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in([
                    'Belum Aktif',
                    'Aktif',
                ]),
            ],
            'approval_document' => [
                'sometimes',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
        ]);

        /*
         * Jika ada PDF baru:
         * hapus file lama lalu simpan file baru.
         */
        if ($request->hasFile('approval_document')) {
            if ($programBudaya->approval_document) {
                Storage::disk('public')->delete(
                    $programBudaya->approval_document
                );
            }

            $validated['approval_document'] = $request
                ->file('approval_document')
                ->store('approval-program-budaya', 'public');
        }

        $programBudaya->update($validated);

        return response()->json([
            'message' => 'Program Budaya berhasil diperbarui.',
            'data' => $programBudaya->fresh()->load('creator:id,name'),
        ]);
    }

    /**
     * Remove the specified Program Budaya.
     */
    public function destroy(string $id)
    {
        $programBudaya = ProgramBudaya::find($id);

        if (!$programBudaya) {
            return response()->json([
                'message' => 'Program Budaya tidak ditemukan.',
            ], 404);
        }

        $programBudaya->delete();

        return response()->json([
            'message' => 'Program Budaya berhasil dihapus.',
        ]);
    }
}