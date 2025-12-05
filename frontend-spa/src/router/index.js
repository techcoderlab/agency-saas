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
          redirect: { name: 'leads' },
        },
        {
          path: 'leads',
          name: 'leads',
          component: LeadsPage,
        },
        {
          path: 'leads/:id',
          name: 'lead-details',
          component: LeadDetails,
        },
        {
          path: 'forms',
          name: 'forms',
          component: FormsPage,
        },
        {
          path: 'ai-chats',
          name: 'ai-chats-index',
          component: AiChatIndexPage
        },
        {
            path: 'ai-chats/:id',
            name: 'ai-chat-room',
            component: AiChatRoomPage
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
          // meta: { requiresAuth: true }
        },

        // New Route Here
        {
          path: 'api-keys',
          name: 'api-keys',
          component: ApiKeysPage
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
  const token = localStorage.getItem('token')

  if (to.meta.requiresAuth && !token) {
    return next({ name: 'login' })
  }

  next()
})

export default router
