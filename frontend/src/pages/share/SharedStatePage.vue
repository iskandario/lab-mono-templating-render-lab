<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStatePersistence } from '@/composables/use-state-persistence'
import { saveState } from '@/api/state-api'
import { useAuthStore } from '@/stores/auth-store'
import { useSandboxStore } from '@/stores/sandbox-store'

const route = useRoute()
const router = useRouter()
const { runRestoreChain } = useStatePersistence()
const auth = useAuthStore()
const sandbox = useSandboxStore()
const error = ref(false)
const loaded = ref(false)
const saving = ref(false)
const savedId = ref<string | null>(null)
const saveError = ref(false)

onMounted(async () => {
  const id = route.params.id as string
  const source = await runRestoreChain(id)
  if (source === 'backend') {
    loaded.value = true
  } else {
    error.value = true
  }
})

async function saveCopy() {
  if (saving.value) return
  saving.value = true
  saveError.value = false
  try {
    const result = await saveState({
      slotA: { ...sandbox.slotA },
      slotB: { ...sandbox.slotB },
      json: sandbox.json,
    })
    sandbox.markSaved(result.id)
    savedId.value = result.id
  } catch {
    saveError.value = true
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <v-container>
    <div v-if="!error && !loaded" class="d-flex justify-center align-center" style="height: 200px">
      <v-progress-circular indeterminate color="primary" />
    </div>
    <div v-else-if="loaded" class="text-center mt-8">
      <p class="text-medium-emphasis mb-4">Состояние загружено.</p>
      <div class="d-flex justify-center ga-3 flex-wrap">
        <v-btn color="primary" @click="router.push('/sandbox')">Открыть в Sandbox</v-btn>
        <v-btn
          v-if="auth.isAuthenticated"
          variant="tonal"
          :loading="saving"
          :disabled="!!savedId"
          @click="saveCopy"
        >
          {{ savedId ? 'Копия сохранена' : 'Сохранить копию' }}
        </v-btn>
        <v-btn v-else variant="tonal" :to="`/login?redirect=${route.fullPath}`">
          Войти, чтобы сохранить копию
        </v-btn>
      </div>
      <p v-if="saveError" class="text-error text-body-2 mt-4">Не удалось сохранить копию.</p>
    </div>
    <div v-else class="text-center mt-8">
      <p class="text-medium-emphasis mb-4">Состояние не найдено.</p>
      <v-btn color="primary" to="/sandbox">Перейти в Sandbox</v-btn>
    </div>
  </v-container>
</template>
