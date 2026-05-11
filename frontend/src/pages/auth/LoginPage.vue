<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth-store'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref<string | null>(null)
const showPassword = ref(false)

const emailRules = [
  (v: string) => !!v || 'Введите email',
  (v: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Некорректный email',
]
const passwordRules = [(v: string) => !!v || 'Введите пароль']

const isValid = computed(
  () =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) && password.value.length > 0,
)

async function submit() {
  if (!isValid.value) return
  loading.value = true
  error.value = null
  try {
    await auth.login(email.value, password.value)
    const redirect = route.query.redirect as string | undefined
    await router.push(redirect || '/sandbox')
  } catch {
    error.value = 'Неверный email или пароль.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <v-card-text class="pa-6">
    <div class="text-h5 font-weight-bold mb-1">Войти</div>
    <p class="text-medium-emphasis text-body-2 mb-5">Рады видеть вас снова!</p>

    <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4">
      {{ error }}
    </v-alert>

    <v-form @submit.prevent="submit">
      <v-text-field
        v-model="email"
        label="Email"
        type="email"
        autocomplete="email"
        :rules="emailRules"
        variant="outlined"
        density="comfortable"
        class="mb-2"
        validate-on="blur"
      />
      <v-text-field
        v-model="password"
        label="Пароль"
        :type="showPassword ? 'text' : 'password'"
        autocomplete="current-password"
        :rules="passwordRules"
        variant="outlined"
        density="comfortable"
        class="mb-1"
        validate-on="blur"
        :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
        @click:append-inner="showPassword = !showPassword"
      />

      <div class="text-right mb-4">
        <router-link to="/forgot-password" class="text-body-2 text-primary">
          Забыли пароль?
        </router-link>
      </div>

      <v-btn
        type="submit"
        color="primary"
        variant="elevated"
        size="large"
        block
        :disabled="!isValid || loading"
        :loading="loading"
      >
        Войти
      </v-btn>
    </v-form>

    <div class="text-center text-body-2 mt-5 text-medium-emphasis">
      Нет аккаунта?
      <router-link to="/register" class="text-primary font-weight-medium">Зарегистрироваться</router-link>
    </div>
  </v-card-text>
</template>
