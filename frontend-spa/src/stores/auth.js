import { defineStore } from 'pinia'
import api from '../utils/request'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    tenant: null,
    token: localStorage.getItem('token') || null,
  }),
  actions: {
    async login(credentials) {
      const { data } = await api.post('/login', credentials)
      this.token = data.token
      this.user = data.user
      this.tenant = data.tenant
      localStorage.setItem('token', this.token)
    },
    async logout() {
      this.user = null
      this.tenant = null
      this.token = null
      localStorage.removeItem('token')
    },
  },
})


