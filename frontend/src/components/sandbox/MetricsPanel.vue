<template>
  <div class="metrics-panel">
    <div v-if="!sandbox.metricsA && !sandbox.metricsB" class="metrics-empty">
      <span class="text-medium-emphasis text-body-2">No benchmark data — click Run Benchmark.</span>
    </div>
    <div v-else class="metrics-content">
      <div v-if="sandbox.metricsA" class="metrics-slot">
        <div class="metrics-slot-label text-caption font-weight-medium text-medium-emphasis">Slot A</div>
        <table class="metrics-table text-body-2">
          <tr>
            <td class="metric-key text-medium-emphasis">avg</td>
            <td class="metric-val">{{ sandbox.metricsA.avgMs }} ms</td>
          </tr>
          <tr>
            <td class="metric-key text-medium-emphasis">min</td>
            <td class="metric-val">{{ sandbox.metricsA.minMs }} ms</td>
          </tr>
          <tr>
            <td class="metric-key text-medium-emphasis">max</td>
            <td class="metric-val">{{ sandbox.metricsA.maxMs }} ms</td>
          </tr>
          <tr>
            <td class="metric-key text-medium-emphasis">p95</td>
            <td class="metric-val">{{ sandbox.metricsA.p95Ms }} ms</td>
          </tr>
          <tr>
            <td class="metric-key text-medium-emphasis">bytes</td>
            <td class="metric-val">{{ sandbox.metricsA.outputBytes }}</td>
          </tr>
        </table>
      </div>

      <v-divider v-if="sandbox.metricsA && sandbox.metricsB" vertical class="mx-3" />

      <div v-if="sandbox.metricsB" class="metrics-slot">
        <div class="metrics-slot-label text-caption font-weight-medium text-medium-emphasis">Slot B</div>
        <table class="metrics-table text-body-2">
          <tr>
            <td class="metric-key text-medium-emphasis">avg</td>
            <td class="metric-val">{{ sandbox.metricsB.avgMs }} ms</td>
          </tr>
          <tr>
            <td class="metric-key text-medium-emphasis">min</td>
            <td class="metric-val">{{ sandbox.metricsB.minMs }} ms</td>
          </tr>
          <tr>
            <td class="metric-key text-medium-emphasis">max</td>
            <td class="metric-val">{{ sandbox.metricsB.maxMs }} ms</td>
          </tr>
          <tr>
            <td class="metric-key text-medium-emphasis">p95</td>
            <td class="metric-val">{{ sandbox.metricsB.p95Ms }} ms</td>
          </tr>
          <tr>
            <td class="metric-key text-medium-emphasis">bytes</td>
            <td class="metric-val">{{ sandbox.metricsB.outputBytes }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useSandboxStore } from '@/stores/sandbox-store'

const sandbox = useSandboxStore()
</script>

<style scoped>
.metrics-panel {
  display: flex;
  align-items: center;
  width: 100%;
  height: 100%;
  padding: 8px 16px;
  overflow: auto;
}

.metrics-empty {
  width: 100%;
  text-align: center;
}

.metrics-content {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.metrics-slot {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.metrics-slot-label {
  margin-bottom: 2px;
}

.metrics-table {
  border-collapse: collapse;
}

.metric-key {
  padding-right: 12px;
  font-size: 0.75rem;
  white-space: nowrap;
}

.metric-val {
  font-size: 0.8rem;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
</style>
