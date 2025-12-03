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
            class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
            active-class="bg-primary/10 text-primary"
            class-active="text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900"
          >
            Leads
          </RouterLink>
          <RouterLink
            to="/admin/forms"
            class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
            active-class="bg-primary/10 text-primary"
            class-active="text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900"
          >
            Forms
          </RouterLink>
          <RouterLink
            v-if="auth.user?.role === 'super_admin'"
            to="/admin/tenants"
            class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
            active-class="bg-primary/10 text-primary"
            class-active="text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900"
          >
            Tenants
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

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <div class="flex-1 overflow-y-auto p-4 sm:p-8 scroll-smooth">
        <div class="max-w-7xl mx-auto w-full">
          <RouterView />
        </div>
      </div>
    </main>

  </div>
</template>