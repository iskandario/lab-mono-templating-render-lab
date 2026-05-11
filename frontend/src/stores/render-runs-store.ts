import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { RenderRun } from '@/types'
import * as renderRunsApi from '@/api/render-runs-api'

export const useRenderRunsStore = defineStore('render-runs', () => {
  const runs = ref<RenderRun[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchRuns() {
    loading.value = true
    error.value = null
    try {
      runs.value = await renderRunsApi.getRuns()
    } catch {
      error.value = 'Не удалось загрузить запуски.'
      runs.value = []
    } finally {
      loading.value = false
    }
  }

  function addRun(run: RenderRun) {
    runs.value.unshift(run)
  }

  return { runs, loading, error, fetchRuns, addRun }
})
