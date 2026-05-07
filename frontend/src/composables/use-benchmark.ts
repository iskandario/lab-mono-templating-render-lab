import { ref } from 'vue'
import { render } from '@/services/render-service'
import type { BenchmarkResult } from '@/types'
import { useSandboxStore } from '@/stores/sandbox-store'

export function useBenchmark() {
  const isRunning = ref(false)
  const benchmarkError = ref<string | null>(null)
  const sandbox = useSandboxStore()

  /** Returns false if the slot's template failed to render (aborts that slot's run). */
  async function runSlot(slot: 'a' | 'b'): Promise<boolean> {
    const s = slot === 'a' ? sandbox.slotA : sandbox.slotB
    // Clamp to [1, 10000] to guard against NaN / zero / out-of-range input
    const n = Math.max(1, Math.min(10000, sandbox.iterations || 1))
    const times: number[] = []
    let lastOutput = ''

    for (let i = 0; i < n; i++) {
      const t0 = performance.now()
      const result = await render(s.engineId, s.code, sandbox.json)
      const t1 = performance.now()
      if ('error' in result) {
        benchmarkError.value = `Slot ${slot.toUpperCase()} render error: ${result.error}`
        return false
      }
      times.push(t1 - t0)
      lastOutput = result.output
    }

    times.sort((a, b) => a - b)
    const avg = times.reduce((a, b) => a + b, 0) / n
    const p95Idx = Math.min(Math.floor((n - 1) * 0.95), times.length - 1)

    const metrics: BenchmarkResult = {
      avgMs: +avg.toFixed(3),
      minMs: +times[0]!.toFixed(3),
      maxMs: +times[times.length - 1]!.toFixed(3),
      p95Ms: +times[p95Idx]!.toFixed(3),
      outputBytes: new TextEncoder().encode(lastOutput).length,
      output: lastOutput,
    }

    if (slot === 'a') sandbox.metricsA = metrics
    else sandbox.metricsB = metrics
    return true
  }

  async function runBenchmark(): Promise<void> {
    if (isRunning.value) return
    isRunning.value = true
    benchmarkError.value = null
    try {
      const okA = await runSlot('a')
      if (okA) await runSlot('b')
    } finally {
      isRunning.value = false
    }
  }

  return { isRunning, benchmarkError, runBenchmark }
}
