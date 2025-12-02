import { createRouter, createWebHistory } from 'vue-router'
import AdminLayout from '../views/admin/AdminLayout.vue'
import SuperAdminTenants from '../views/admin/SuperAdminTenants.vue'
import LeadsPage from '../views/admin/LeadsPage.vue'
import FormsPage from '../views/admin/FormsPage.vue'
import PublicFormPage from '../views/public/PublicFormPage.vue'
import LoginPage from '../views/auth/LoginPage.vue'

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
          path: 'forms',
          name: 'forms',
          component: FormsPage,
        },
        {
          path: 'tenants',
          name: 'superadmin-tenants',
          component: SuperAdminTenants,
          meta: { role: 'super_admin' },
        },
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
