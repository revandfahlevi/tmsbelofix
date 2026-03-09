import api from '@/lib/axios'
import type { LoginPayload, AuthResponse, MeResponse } from '@/types/auth'

export const useAuthApi = () => {
  const login = (payload: LoginPayload) =>
    api.post<AuthResponse>('/auth/login', payload)

  const logout = () =>
    api.post('/auth/logout')

  const logoutAll = () =>
    api.post('/auth/logout-all')

  const me = () =>
    api.get<MeResponse>('/auth/me')

  const refresh = () =>
    api.post('/auth/refresh')

  const changePassword = (payload: {
    current_password: string
    new_password: string
    new_password_confirmation: string
  }) => api.put('/auth/change-password', payload)

  return { login, logout, logoutAll, me, refresh, changePassword }
}