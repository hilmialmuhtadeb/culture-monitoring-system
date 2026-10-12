export interface UserDivisi {
  id: number
  nama_divisi: string
  divisi_alias: string | null
}

export interface UserRole {
  id: number
  name: string
}

export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  id_divisi: number | null
  jabatan: string | null
  status: boolean
  created_at?: string
  updated_at?: string
  divisi: UserDivisi | null
  roles: UserRole[]
}

export interface UserFormData {
  name: string
  email: string
  jabatan: string
  status: boolean
  id_divisi: number | null
  role: number | null
  password: string
  password_confirmation: string
}