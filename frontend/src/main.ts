// Pug (and some other Node-targeting libs) reference `process` at runtime.
// This shim must be the first import so it runs before any lazy-loaded engine.
import './shims/node-process-shim'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import { restoreTheme } from './composables/use-theme-toggle'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth-store'

const vuetify = createVuetify({
  theme: {
    defaultTheme: restoreTheme(),
  },
})

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
await useAuthStore(pinia).initializeAuth()
app.use(router)
app.use(vuetify)

app.mount('#app')
