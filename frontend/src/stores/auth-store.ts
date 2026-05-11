import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { User } from '@/types'
import * as authApi from '@/api/auth-api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(loadStoredUser())
  const isInitialized = ref(false)
  const isAuthenticated = computed(() => !!user.value)

  async function login(email: string, password: string) {
    user.value = await authApi.login(email, password)
    localStorage.setItem('auth_user', JSON.stringify(user.value))
  }

  async function register(email: string, password: string, name?: string) {
    user.value = await authApi.register(email, password, name)
    localStorage.setItem('auth_user', JSON.stringify(user.value))
  }

  function clearSession() {
    user.value = null
    localStorage.removeItem('auth_user')
  }

  async function logout() {
    try {
      if (user.value) await authApi.logout()
    } catch {
    } finally {
      clearSession()
    }
  }

  async function fetchCurrentUser() {
    user.value = await authApi.getMe()
    if (user.value) {
      localStorage.setItem('auth_user', JSON.stringify(user.value))
    } else {
      clearSession()
    }
  }

  async function initializeAuth() {
    try {
      await fetchCurrentUser()
    } finally {
      isInitialized.value = true
    }
  }

  return {
    user,
    isAuthenticated,
    isInitialized,
    login,
    register,
    logout,
    clearSession,
    fetchCurrentUser,
    initializeAuth,
  }
})

function loadStoredUser(): User | null {
  try {
    const stored = localStorage.getItem('auth_user')
    return stored ? (JSON.parse(stored) as User) : null
  } catch {
    localStorage.removeItem('auth_user')
    return null
  }
}
