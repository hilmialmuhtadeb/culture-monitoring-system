export type ProgramBudayaStatus =
  | 'Belum Aktif'
  | 'Aktif'

export interface ProgramBudayaCreator {
  id: number
  name: string
}

export interface ProgramBudaya {
  id: number
  nama: string
  deskripsi: string
  frekuensi: number
  tahun: number
  status: ProgramBudayaStatus
  approval_document: string
  created_by: number
  creator?: ProgramBudayaCreator
}