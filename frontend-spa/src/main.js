import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'
import { vCan } from './directives/v-can'
import { vModule } from './directives/v-module'

const app = createApp(App)

app.use(createPinia())
app.use(router)

// Register custom directives
app.directive('can', vCan)
app.directive('module', vModule)

const authStore = useAuthStore()

// If a token exists, try to bootstrap user data before mounting the app
if (authStore.token) {
  authStore.bootstrap().finally(() => {
    app.mount('#app')
  })
} else {
  app.mount('#app')
}
