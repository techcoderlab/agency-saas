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

if (localStorage.getItem('theme') === 'dark') {
  document.documentElement.classList.add('dark')
}

const logout = async () => {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen w-screen flex transition-colors duration-300">
    <aside class="sidebar flex flex-col justify-between">
      <div>
        <div class="pl-2 pb-8 pt-4 flex items-center gap-2">
          <span class="heading-lg">Agency SaaS</span>
        </div>
        <nav class="space-y-1">
          <RouterLink
            to="/admin/leads"
            class="block px-4 py-3 rounded-xl font-semibold transition hover:bg-violet-500/10 dark:hover:bg-violet-400/10 focus:bg-violet-500/10 dark:focus:bg-violet-400/10"
            active-class="bg-violet-500/20 dark:bg-violet-500/10 text-violet-500 dark:text-violet-300"
          >
            Leads
          </RouterLink>
          <RouterLink
            to="/admin/forms"
            class="block px-4 py-3 rounded-xl font-semibold transition hover:bg-violet-500/10 dark:hover:bg-violet-400/10 focus:bg-violet-500/10 dark:focus:bg-violet-400/10"
            active-class="bg-violet-500/20 dark:bg-violet-500/10 text-violet-500 dark:text-violet-300"
          >
            Forms
          </RouterLink>
          <RouterLink
            v-if="auth.user?.role === 'super_admin'"
            to="/admin/tenants"
            class="block px-4 py-3 rounded-xl font-semibold transition hover:bg-violet-500/10 dark:hover:bg-violet-400/10 focus:bg-violet-500/10 dark:focus:bg-violet-400/10"
            active-class="bg-violet-500/20 dark:bg-violet-500/10 text-violet-500 dark:text-violet-300"
          >
            Tenants
          </RouterLink>
        </nav>
      </div>
      <div class="pb-2">
        <div class="flex items-center gap-2 mb-4 px-3">
          <span class="text-xs opacity-60">{{ auth.user?.email }}</span>
          <button
            class="text-xs px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-full border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
            @click="toggleDark"
          >
            Theme
          </button>
        </div>
        <button
          class="btn-danger w-full text-center mt-1"
          @click="logout"
        >
          Logout
        </button>
      </div>
    </aside>
    <main class="main flex-1 flex flex-col">
      <div class="max-w-7xl w-full mx-auto">
        <div class="card my-4 p-6 md:p-8">
          <RouterView />
        </div>
      </div>
    </main>
  </div>
</template>


