<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth-store'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const loading = ref(false)
const error = ref<string | null>(null)
const showPassword = ref(false)
const showConfirm = ref(false)

const emailRules = [
  (v: string) => !!v || 'Введите email',
  (v: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Некорректный email',
]
const passwordRules = [
  (v: string) => !!v || 'Введите пароль',
  (v: string) => v.length >= 8 || 'Минимум 8 символов',
]
const confirmRules = [
  (v: string) => !!v || 'Подтвердите пароль',
  (v: string) => v === password.value || 'Пароли не совпадают',
]

const isValid = computed(
  () =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) &&
    password.value.length >= 8 &&
    confirmPassword.value === password.value,
)

async function submit() {
  if (!isValid.value) return
  loading.value = true
  error.value = null
  try {
    await auth.register(email.value, password.value)
    await router.push('/sandbox')
  } catch {
    error.value = 'Ошибка регистрации. Этот email уже используется.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <v-card-text class="pa-6">
    <div class="text-h5 font-weight-bold mb-1">Создать аккаунт</div>
    <p class="text-medium-emphasis text-body-2 mb-5">Начните сравнивать шаблоны прямо сейчас.</p>

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
        autocomplete="new-password"
        :rules="passwordRules"
        variant="outlined"
        density="comfortable"
        class="mb-2"
        validate-on="blur"
        :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
        @click:append-inner="showPassword = !showPassword"
      />
      <v-text-field
        v-model="confirmPassword"
        label="Подтвердите пароль"
        :type="showConfirm ? 'text' : 'password'"
        autocomplete="new-password"
        :rules="confirmRules"
        variant="outlined"
        density="comfortable"
        class="mb-4"
        validate-on="blur"
        :append-inner-icon="showConfirm ? 'mdi-eye-off' : 'mdi-eye'"
        @click:append-inner="showConfirm = !showConfirm"
      />

      <v-btn
        type="submit"
        color="primary"
        variant="elevated"
        size="large"
        block
        :disabled="!isValid || loading"
        :loading="loading"
      >
        Зарегистрироваться
      </v-btn>
    </v-form>

    <div class="text-center text-body-2 mt-5 text-medium-emphasis">
      Уже есть аккаунт?
      <router-link to="/login" class="text-primary font-weight-medium">Войти</router-link>
    </div>
  </v-card-text>
</template>
