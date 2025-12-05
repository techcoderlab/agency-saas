<script setup>
import { RouterView, RouterLink, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

// Theme Toggling Logic
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

// Initialize theme on mount (if not already handled in main.js)
if (localStorage.getItem('theme') === 'dark') {
  document.documentElement.classList.add('dark')
}

const logout = async () => {
  await auth.logout()
  router.push('/login')
}

const navigation = [
  { name: 'Dashboard', href: '/admin', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: 'Leads', href: '/admin/leads', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
  { name: 'Forms', href: '/admin/forms', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { name: 'Webhooks', href: '/admin/webhooks', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
  // API Keys Link Added Here
  { name: 'API Keys', href: '/admin/api-keys', icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11.536 16.464l-1.414 1.414a1 1 0 01-1.414 0l-1.414-1.414a1 1 0 010-1.414l1.414-1.414L15 7zm3 5a1 1 0 11-2 0 1 1 0 012 0z' },
]
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex transition-colors duration-300">
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-slate-950 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between">
      <div>
        <div class="flex h-16 items-center px-6 border-b border-slate-200 dark:border-slate-800">
          <h1 class="text-xl font-bold text-slate-900 dark:text-white">Agency SaaS</h1>
        </div>
        <nav class="space-y-1 px-3 py-4">
          <RouterLink
            v-for="item in navigation"
            :key="item.name"
            :to="item.href"
            :class="[
              route.path === item.href || (item.href !== '/admin' && route.path.startsWith(item.href))
                ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400'
                : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-900',
              'group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors'
            ]"
          >
            <svg class="mr-3 h-5 w-5 flex-shrink-0" :class="route.path === item.href ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 group-hover:text-slate-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
            </svg>
            {{ item.name }}
          </RouterLink>
        </nav>
      </div>
      
      <div class="border-t border-slate-200 dark:border-slate-800 p-4 space-y-3">
         <div class="flex items-center justify-between px-2">
             <span class="text-xs text-slate-500 truncate max-w-[100px]" :title="auth.user?.email">{{ auth.user?.email }}</span>
             <button
                class="flex items-center justify-center p-1.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 transition-colors"
                @click="toggleDark"
                title="Toggle Theme"
              >
                 <svg class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                 </svg>
                 <svg class="w-4 h-4 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                 </svg>
              </button>
         </div>

        <button @click="logout" class="w-full flex items-center justify-center px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
          <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Logout
        </button>
      </div>
    </div>

    <div class="pl-64 w-full">
      <main class="py-8 px-8">
        <RouterView />
      </main>
    </div>
  </div>
</template>