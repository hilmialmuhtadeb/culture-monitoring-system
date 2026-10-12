export type LaporanStatus =
  | 'Draft'
  | 'Menunggu Approval'
  | 'Rejected'
  | 'Approved'

export interface LaporanProgramBudaya {
  id: number
  nama: string
  deskripsi?: string
  frekuensi?: string
  tahun?: number
  status: 'Belum Aktif' | 'Aktif'
}

export interface LaporanDivisi {
  id: number
  nama_divisi: string
  divisi_alias: string | null
}

export interface LaporanUser {
  id: number
  name: string
  email?: string
  id_divisi: number | null
  divisi?: LaporanDivisi | null
}

export interface LaporanFokusArea {
  id: number
  nama: string
}

export interface LaporanApprover {
  id: number
  name: string
}

export interface Laporan {
  id: number
  program_budaya_id: number
  user_id: number
  fokus_area_id: number

  nama: string
  alasan: string
  alasan_fokus_area: string | null
  pimpinan_hadir: string[] | null
  tanggal_realisasi: string | null
  video_url: string | null
  pimpinan_terlibat: boolean | null
  change_agent_berpartisipasi: boolean | null
  target_divisi_60_90_persen: boolean | null
  image: string | null
  images: string[] | null

  status: LaporanStatus

  rejection_reason: string | null
  approved_by: number | null
  approved_at: string | null

  created_at?: string
  updated_at?: string

  programBudaya?: LaporanProgramBudaya
  program_budaya?: LaporanProgramBudaya
  user?: LaporanUser
  fokusArea?: LaporanFokusArea
  fokus_area?: LaporanFokusArea
  approver?: LaporanApprover | null
}