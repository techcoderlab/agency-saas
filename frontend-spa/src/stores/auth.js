import { defineStore } from 'pinia'
import api from '../utils/request'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    permissions: [],
    activeTenant: null,
    enabledModules: [],
    moduleNav: [], // New state
  }),
  actions: {
    async bootstrap() {
      try {
        const { data } = await api.get('/bootstrap')
        this.user = data.user
        this.permissions = data.permissions
        this.activeTenant = data.active_tenant
        this.enabledModules = data.enabled_modules
        this.moduleNav = data.module_nav // Store rich nav
      } catch (error) {
        await this.logout()
        throw error
      }
    },
    async login(credentials) {
      const { data } = await api.post('/login', credentials)
      this.token = data.token
      localStorage.setItem('token', this.token)
      // After login, bootstrap the app data
      await this.bootstrap()
    },
    async logout() {
      // Here you might want to also call a backend logout endpoint if it exists
      // await api.post('/logout')
      this.user = null
      this.token = null
      this.permissions = []
      this.activeTenant = null
      this.enabledModules = []
      localStorage.removeItem('token')
    },
  },
})
