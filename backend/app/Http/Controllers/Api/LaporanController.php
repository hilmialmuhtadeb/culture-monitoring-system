<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\LaporanExcelExport;
use App\Models\Laporan;
use App\Models\ProgramBudaya;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LaporanController extends Controller
{
     /**
      * Menampilkan daftar laporan sesuai cakupan permission user.
      * Pemilik dapat melihat semua status laporannya sendiri, sedangkan
      * reviewer hanya melihat laporan yang sudah disubmit atau approved.
      */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Laporan::with([
            'programBudaya:id,nama,status',
            'user:id,name,id_divisi',
            'user.divisi:id,nama_divisi,divisi_alias',
            'fokusArea:id,nama',
            'approver:id,name',
        ])->latest();

        if ($user->can('view own laporan')) {
            $query->where('user_id', $user->id);
        } elseif ($user->can('view all laporan')) {
            $query->whereIn('status', [
                'Menunggu Approval',
                'Approved',
            ]);
        } else {
            $query->whereRaw('1 = 0');
        }

        return response()->json([
            'message' => 'Data laporan berhasil diambil.',
            'data' => $query->get(),
        ]);
    }

    public function export(Request $request, LaporanExcelExport $export)
    {
        abort_unless($request->user()->can('view all laporan'), 403);

        $reports = Laporan::with([
            'programBudaya:id,nama,deskripsi,status',
            'user:id,name,id_divisi',
            'user.divisi:id,nama_divisi,divisi_alias',
            'fokusArea:id,nama',
        ])
            ->whereIn('status', ['Menunggu Approval', 'Approved'])
            ->latest()
            ->get();

        return $export->download($reports);
    }

    /**
     * Menampilkan detail laporan.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $laporan = Laporan::with([
            'programBudaya:id,nama,deskripsi,frekuensi,tahun,status',
            'user:id,name,email,id_divisi',
            'user.divisi:id,nama_divisi,divisi_alias',
            'fokusArea:id,nama',
            'approver:id,name',
        ])->find($id);

        if (!$laporan) {
            return response()->json([
                'message' => 'Laporan tidak ditemukan.',
            ], 404);
        }

        $isOwner = $laporan->user_id === $user->id;
        $canViewOwn = $user->can('view own laporan') && $isOwner;
        $canViewReviewed = $user->can('view all laporan') && in_array(
            $laporan->status,
            ['Menunggu Approval', 'Approved'],
            true,
        );

        if (!$canViewOwn && !$canViewReviewed) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke laporan ini.',
            ], 403);
        }

        return response()->json([
            'message' => 'Detail laporan berhasil diambil.',
            'data' => $laporan,
        ]);
    }

    /**
     * Membuat laporan baru.
     *
     * Hanya CA yang dapat membuat laporan.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        /*
         * Pastikan user memang CA.
         */
        $validated = $this->validateReportRequest($request, [
            'program_budaya_id' => [
                'required',
                'integer',
                'exists:program_budayas,id',
            ],

            'fokus_area_id' => [
                'required',
                'integer',
                'exists:fokus_areas,id',
            ],

            'nama' => [
                'required',
                'string',
                'max:255',
            ],

            'alasan' => [
                'required',
                'string',
            ],

            'alasan_fokus_area' => [
                'required',
                'string',
            ],

            'pimpinan_hadir' => [
                'required',
                'array',
                'min:1',
            ],

            'pimpinan_hadir.*' => [
                'required',
                'string',
                Rule::in(['EVP', 'VP', 'Senior Analyst', 'Analyst']),
            ],

            'tanggal_realisasi' => [
                'required',
                'date',
            ],

            'video_url' => [
                'required',
                'url',
                'max:2048',
            ],

            'pimpinan_terlibat' => ['required', 'boolean'],
            'change_agent_berpartisipasi' => ['required', 'boolean'],
            'target_divisi_60_90_persen' => ['required', 'boolean'],

            'images' => [
                'required',
                'array',
                'min:2',
            ],

            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:20480',
            ],
        ]);

        /*
         * Program harus sedang dijalankan.
         *
         * CA tidak boleh membuat laporan untuk
         * program yang masih Belum Aktif.
         */
        $programBudaya = ProgramBudaya::find(
            $validated['program_budaya_id']
        );

        if (!$programBudaya) {
            return response()->json([
                'message' => 'Program Budaya tidak ditemukan.',
            ], 404);
        }

        if ($programBudaya->status !== 'Aktif') {
            return response()->json([
                'message' => 'Program Budaya belum dapat dibuatkan laporan.',
            ], 422);
        }

        /*
         * Upload gambar jika ada.
         */
        $imagePaths = [];

        foreach ($request->file('images', []) as $image) {
            $imagePaths[] = $image->store('laporan', 'public');
        }

        $laporan = Laporan::create([
            'program_budaya_id' => $validated['program_budaya_id'],
            'user_id' => $user->id,
            'fokus_area_id' => $validated['fokus_area_id'],
            'nama' => $validated['nama'],
            'alasan' => $validated['alasan'],
            'alasan_fokus_area' => $validated['alasan_fokus_area'],
            'pimpinan_hadir' => $validated['pimpinan_hadir'],
            'tanggal_realisasi' => $validated['tanggal_realisasi'],
            'video_url' => $validated['video_url'] ?? null,
            'pimpinan_terlibat' => $validated['pimpinan_terlibat'],
            'change_agent_berpartisipasi' => $validated['change_agent_berpartisipasi'],
            'target_divisi_60_90_persen' => $validated['target_divisi_60_90_persen'],
            'images' => $imagePaths,
            'image' => $imagePaths[0],
            'status' => 'Draft',
        ]);

        return response()->json([
            'message' => 'Laporan berhasil dibuat.',
            'data' => $laporan->load([
                'programBudaya:id,nama,status',
                'user:id,name,id_divisi',
                'user.divisi:id,nama_divisi,divisi_alias',
                'fokusArea:id,nama',
            ]),
        ], 201);
    }

    /**
     * Mengubah laporan.
     *
     * CA hanya dapat mengedit laporan:
     * - Draft
     * - Rejected
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();

        $laporan = Laporan::find($id);

        if (!$laporan) {
            return response()->json([
                'message' => 'Laporan tidak ditemukan.',
            ], 404);
        }

        /*
         * CA hanya boleh mengedit laporan miliknya.
         */
        if (
            $user->can('manage laporan') &&
            $laporan->user_id !== $user->id
        ) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengedit laporan ini.',
            ], 403);
        }

        /*
         * Untuk sementara update hanya diperbolehkan
         * pada Draft dan Rejected.
         */
        if (
            $user->can('manage laporan') &&
            !in_array(
                $laporan->status,
                ['Draft', 'Rejected'],
                true
            )
        ) {
            return response()->json([
                'message' => 'Laporan hanya dapat diedit saat Draft atau Rejected.',
            ], 422);
        }

        $validated = $this->validateReportRequest($request, [
            'program_budaya_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:program_budayas,id',
            ],

            'fokus_area_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:fokus_areas,id',
            ],

            'nama' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'alasan' => [
                'sometimes',
                'required',
                'string',
            ],

            'alasan_fokus_area' => [
                'sometimes',
                'required',
                'string',
            ],

            'pimpinan_hadir' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],

            'pimpinan_hadir.*' => [
                'required',
                'string',
                Rule::in(['EVP', 'VP', 'Senior Analyst', 'Analyst']),
            ],

            'tanggal_realisasi' => [
                'sometimes',
                'required',
                'date',
            ],

            'video_url' => [
                'required',
                'url',
                'max:2048',
            ],

            'pimpinan_terlibat' => ['sometimes', 'required', 'boolean'],
            'change_agent_berpartisipasi' => ['sometimes', 'required', 'boolean'],
            'target_divisi_60_90_persen' => ['sometimes', 'required', 'boolean'],

            'images' => [
                'sometimes',
                'required',
                'array',
                'min:2',
            ],

            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:20480',
            ],
        ]);

        /*
         * Jika program budaya diganti,
         * pastikan program tersebut sedang aktif.
         */
        if (isset($validated['program_budaya_id'])) {
            $programBudaya = ProgramBudaya::find(
                $validated['program_budaya_id']
            );

            if (!$programBudaya || $programBudaya->status !== 'Aktif') {
                return response()->json([
                    'message' => 'Program Budaya belum dapat dibuatkan laporan.',
                ], 422);
            }
        }

        /*
         * Upload gambar baru jika diberikan.
         */
        if ($request->hasFile('images')) {
            $oldImages = $laporan->images ?: array_filter([$laporan->image]);

            foreach ($oldImages as $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            $imagePaths = [];

            foreach ($request->file('images', []) as $image) {
                $imagePaths[] = $image->store('laporan', 'public');
            }

            $validated['images'] = $imagePaths;
            $validated['image'] = $imagePaths[0] ?? null;
        }

        /*
         * Jika laporan sebelumnya Rejected,
         * alasan penolakan lama dihapus karena
         * CA sedang memperbaiki laporan.
         */
        if ($laporan->status === 'Rejected') {
            $validated['rejection_reason'] = null;
        }

        $laporan->update($validated);

        return response()->json([
            'message' => 'Laporan berhasil diperbarui.',
            'data' => $laporan->fresh()->load([
                'programBudaya:id,nama,status',
                'user:id,name,id_divisi',
                'user.divisi:id,nama_divisi,divisi_alias',
                'fokusArea:id,nama',
                'approver:id,name',
            ]),
        ]);
    }

    /**
     * Menghapus laporan.
     *
     * Untuk sementara hanya Tim Budaya
     * yang dapat menghapus laporan.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $laporan = Laporan::find($id);

        if (!$laporan) {
            return response()->json([
                'message' => 'Laporan tidak ditemukan.',
            ], 404);
        }

        $laporan->delete();

        return response()->json([
            'message' => 'Laporan berhasil dihapus.',
        ]);
    }

    /**
     * Submit laporan dari Draft/Rejected
     * menjadi Menunggu Approval.
     */
    public function submit(Request $request, string $id)
    {
        $user = $request->user();

        $laporan = Laporan::find($id);

        if (!$laporan) {
            return response()->json([
                'message' => 'Laporan tidak ditemukan.',
            ], 404);
        }

        /*
         * Hanya pemilik laporan yang dapat submit.
         */
        if (
            !$user->can('manage laporan') ||
            $laporan->user_id !== $user->id
        ) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk submit laporan ini.',
            ], 403);
        }

        /*
         * Submit hanya dari Draft atau Rejected.
         */
        if (
            !in_array(
                $laporan->status,
                ['Draft', 'Rejected'],
                true
            )
        ) {
            return response()->json([
                'message' => 'Laporan tidak dapat disubmit pada status saat ini.',
            ], 422);
        }

        $laporan->update([
            'status' => 'Menunggu Approval',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Laporan berhasil disubmit dan menunggu approval.',
            'data' => $laporan->fresh()->load([
                'programBudaya:id,nama,status',
                'user:id,name,id_divisi',
                'user.divisi:id,nama_divisi,divisi_alias',
                'fokusArea:id,nama',
            ]),
        ]);
    }

    /**
     * Approve laporan.
     *
     * Hanya Tim Budaya.
     */
    public function approve(Request $request, string $id)
    {
        $user = $request->user();

        $laporan = Laporan::find($id);

        if (!$laporan) {
            return response()->json([
                'message' => 'Laporan tidak ditemukan.',
            ], 404);
        }

        if ($laporan->status !== 'Menunggu Approval') {
            return response()->json([
                'message' => 'Hanya laporan yang menunggu approval yang dapat disetujui.',
            ], 422);
        }

        $laporan->update([
            'status' => 'Approved',
            'rejection_reason' => null,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Laporan berhasil disetujui.',
            'data' => $laporan->fresh()->load([
                'programBudaya:id,nama,status',
                'user:id,name,id_divisi',
                'user.divisi:id,nama_divisi,divisi_alias',
                'fokusArea:id,nama',
                'approver:id,name',
            ]),
        ]);
    }

    /**
     * Reject laporan.
     *
     * Hanya Tim Budaya.
     */
    public function reject(Request $request, string $id)
    {
        $user = $request->user();

        $laporan = Laporan::find($id);

        if (!$laporan) {
            return response()->json([
                'message' => 'Laporan tidak ditemukan.',
            ], 404);
        }

        if ($laporan->status !== 'Menunggu Approval') {
            return response()->json([
                'message' => 'Hanya laporan yang menunggu approval yang dapat ditolak.',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => [
                'required',
                'string',
            ],
        ]);

        $laporan->update([
            'status' => 'Rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return response()->json([
            'message' => 'Laporan berhasil ditolak.',
            'data' => $laporan->fresh()->load([
                'programBudaya:id,nama,status',
                'user:id,name,id_divisi',
                'user.divisi:id,nama_divisi,divisi_alias',
                'fokusArea:id,nama',
                'approver:id,name',
            ]),
        ]);
    }

    private function validateReportRequest(Request $request, array $rules): array
    {
        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request): void {
            $images = $request->file('images', []);

            if ($images instanceof UploadedFile) {
                $images = [$images];
            }

            $totalSize = collect($images)
                ->filter(fn ($image) => $image instanceof UploadedFile)
                ->sum(fn (UploadedFile $image) => $image->getSize() ?: 0);

            if ($totalSize > 20 * 1024 * 1024) {
                $validator->errors()->add(
                    'images',
                    'Ukuran total seluruh foto tidak boleh lebih dari 20 MB.'
                );
            }
        });

        return $validator->validate();
    }
}