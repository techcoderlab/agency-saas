<script setup>
import { RouterView, RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const toggleDark = () => {
  const root = document.documentElement
  if (root.classList.contains('dark')) {
    root.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  } else {
    root.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  }
}

// Initialize theme
if (localStorage.getItem('theme') === 'dark') {
  document.documentElement.classList.add('dark')
}

const logout = async () => {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="flex h-screen w-full bg-slate-50 dark:bg-slate-900 overflow-hidden transition-colors duration-300">
    
    <aside class="w-64 flex-shrink-0 flex flex-col justify-between bg-white dark:bg-slate-950 border-r border-slate-200 dark:border-slate-800 z-20">
      <div>
        <div class="pl-6 pb-8 pt-6 flex items-center gap-2">
          <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">Agency SaaS</span>
        </div>
        <nav class="space-y-1 px-3">
          <RouterLink
            to="/admin/leads"
            class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors flex gap-2"
            active-class="bg-primary/10 text-primary"
            class-active="text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900"
          >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3a2 2 0 01-2-2V7a2 2 0 012-2h12a2 2 0 012 2v3.292A3 3 0 1117 16v-2.646M20 18a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
            Leads
          </RouterLink>
          <RouterLink
            to="/admin/forms"
            class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors flex gap-2"
            active-class="bg-primary/10 text-primary"
            class-active="text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900"
          >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
            Forms
          </RouterLink>
          <RouterLink
            v-if="auth.user?.role === 'super_admin'"
            to="/admin/tenants"
            class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors flex gap-2"
            active-class="bg-primary/10 text-primary"
            class-active="text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900"
          >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.225-.564-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
            Tenants
          </RouterLink>
          <RouterLink
            to="/admin/webhooks"
            class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors flex gap-2"
            active-class="bg-primary/10 text-primary"
            class-active="text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900"
          >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          Webhooks
          </RouterLink>
        </nav>
      </div>
      
      <div class="p-4 border-t border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
          <span class="text-xs text-slate-500 truncate max-w-[100px]">{{ auth.user?.email }}</span>
          <button
            class="text-xs px-2 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-primary transition-colors"
            @click="toggleDark"
          >
            Theme
          </button>
        </div>
        <button
          class="w-full py-2 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 rounded-lg transition-colors"
          @click="logout"
        >
          Logout
        </button>
      </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-200/80 dark:bg-slate-900">
      <div class="flex-1 overflow-y-auto p-4 sm:p-8 scroll-smooth">
        <div class="max-w-7xl mx-auto w-full">
          <RouterView />
        </div>
      </div>
    </main>

  </div>
</template>