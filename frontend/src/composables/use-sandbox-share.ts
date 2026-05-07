import { ref, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth-store'
import { useSandboxStore } from '@/stores/sandbox-store'
import { saveState } from '@/api/state-api'

export function useSandboxShare() {
  const isSaving = ref(false)
  const feedbackMsg = ref<string | null>(null)
  const auth = useAuthStore()
  const sandbox = useSandboxStore()

  let feedbackTimer: ReturnType<typeof setTimeout> | null = null

  function showFeedback(msg: string) {
    if (feedbackTimer) clearTimeout(feedbackTimer)
    feedbackMsg.value = msg
    feedbackTimer = setTimeout(() => {
      feedbackMsg.value = null
    }, 2500)
  }

  onUnmounted(() => {
    if (feedbackTimer) clearTimeout(feedbackTimer)
  })

  async function ensureSaved(): Promise<string | null> {
    if (sandbox.savedStateId) return sandbox.savedStateId
    try {
      const { id } = await saveState({
        slotA: { engineId: sandbox.slotA.engineId, code: sandbox.slotA.code },
        slotB: { engineId: sandbox.slotB.engineId, code: sandbox.slotB.code },
        json: sandbox.json,
      })
      sandbox.markSaved(id)
      return id
    } catch {
      return null
    }
  }

  async function save(): Promise<void> {
    if (isSaving.value) return
    isSaving.value = true
    try {
      if (auth.isAuthenticated) {
        const id = await ensureSaved()
        showFeedback(id ? 'Saved' : 'Save failed')
      } else {
        const state = JSON.stringify({
          slotA: { engineId: sandbox.slotA.engineId, code: sandbox.slotA.code },
          slotB: { engineId: sandbox.slotB.engineId, code: sandbox.slotB.code },
          json: sandbox.json,
        })
        const ok = await navigator.clipboard.writeText(state).then(() => true).catch(() => false)
        showFeedback(ok ? 'State copied to clipboard' : 'Clipboard unavailable')
      }
    } finally {
      isSaving.value = false
    }
  }

  async function share(): Promise<void> {
    if (isSaving.value) return
    if (!auth.isAuthenticated) {
      showFeedback('Sign in to share')
      return
    }
    isSaving.value = true
    try {
      const id = await ensureSaved()
      if (id) {
        const url = `${window.location.origin}/s/${id}`
        const ok = await navigator.clipboard.writeText(url).then(() => true).catch(() => false)
        showFeedback(ok ? 'Link copied to clipboard' : 'Clipboard unavailable')
      } else {
        showFeedback('Share failed')
      }
    } finally {
      isSaving.value = false
    }
  }

  return { isSaving, feedbackMsg, save, share }
}
