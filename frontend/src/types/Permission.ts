export interface PermissionRole {
  id: number
  name: string
}

export interface Permission {
  id: number
  name: string
  guard_name: string
  created_at?: string
  updated_at?: string
  roles: PermissionRole[]
}

export interface PermissionFormData {
  name: string
}