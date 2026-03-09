export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'driver' | 'user'
  phone?: string
  status: string
  employee_id?: string
  abilities?: string[]
}

export interface LoginPayload {
  email: string
  password: string
}

export interface AuthResponse {
  success: boolean
  message: string
  data: {
    user: User
    access_token: string    // ← fix
    refresh_token: string
    token_type: string
    expires_in: number
  }
}

export interface MeResponse {
  success: boolean
  data: User
}