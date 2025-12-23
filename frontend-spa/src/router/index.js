import { createRouter, createWebHistory } from 'vue-router'
import AdminLayout from '../views/admin/AdminLayout.vue'
import SuperAdminTenants from '../views/admin/SuperAdminTenants.vue'
import LeadsPage from '../views/admin/LeadsPage.vue'
import FormsPage from '../views/admin/FormsPage.vue'
import PublicFormPage from '../views/public/PublicFormPage.vue'
import LoginPage from '../views/auth/LoginPage.vue'
import LeadDetails from '../views/admin/LeadDetails.vue'
import WebhooksPage from '../views/admin/WebhooksPage.vue'
import ApiKeysPage from '../views/admin/ApiKeysPage.vue' // Import the new view
import AiChatIndexPage from '../views/admin/ai-chat/AiChatIndex.vue'
import AiChatRoomPage from '../views/admin/ai-chat/AiChatRoom.vue'
import AgencyDashboard from '@/views/admin/AgencyDashboard.vue'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: AgencyDashboard,
          // redirect: { name: 'leads' },
        },
        {
          path: 'leads',
          name: 'leads',
          component: LeadsPage,
          meta: { module: 'leads' }, // Example of module guard
        },
        {
          path: 'leads/:id',
          name: 'lead-details',
          component: LeadDetails,
          meta: { module: 'leads' }, // Example of module guard
        },
        {
          path: 'forms',
          name: 'forms',
          component: FormsPage,
          meta: { module: 'forms' }, // Example of module guard
        },
        {
          path: 'ai-chats',
          name: 'ai-chats-index',
          component: AiChatIndexPage,
          meta: { module: 'ai_chat' }, // Example of module guard
        },
        {
            path: 'ai-chats/:id',
            name: 'ai-chat-room',
            component: AiChatRoomPage,
            meta: { module: 'ai_chat' }, // Example of module guard
        },
        {
          path: 'tenants',
          name: 'superadmin-tenants',
          component: SuperAdminTenants,
          meta: { role: 'super_admin' },
        },
        {
          path: 'webhooks',
          name: 'webhooks',
          component: WebhooksPage,
          meta: { module: 'webhooks' }, // Example of module guard
        },

        // New Route Here
        {
          path: 'api-keys',
          name: 'api-keys',
          component: ApiKeysPage,
          meta: { module: 'api_keys' }, // Example of module guard
        }
      ],
    },
    {
      path: '/form/:uuid',
      name: 'public-form',
      component: PublicFormPage,
      meta: { public: true },
    },
  ],
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const token = authStore.token

  if (to.meta.requiresAuth && !token) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  // Check for module access if the user is authenticated
  if (token && to.meta.module) {
    if (!authStore.enabledModules.includes(to.meta.module)) {
      // Maybe show a "Module not enabled" page in the future
      return next({ name: 'dashboard' })
    }
  }

  // Check for role access
  if (token && to.meta.role) {
    // This is a simple role check, you might have a more complex logic with permissions
    if (!authStore.user || !authStore.user.roles.some(role => role.name === to.meta.role)) {
      return next({ name: 'dashboard' })
    }
  }

  next()
})

export default router
