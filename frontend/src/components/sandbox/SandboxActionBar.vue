<template>
  <div class="action-bar">
    <div class="action-left">
      <v-btn
        size="small"
        variant="tonal"
        :loading="isRunning"
        @click="runBenchmark"
      >
        Run Benchmark
      </v-btn>

      <v-btn
        size="small"
        :variant="sandbox.mode === 'compare' ? 'tonal' : 'text'"
        @click="sandbox.mode = sandbox.mode === 'compare' ? 'editor' : 'compare'"
      >
        Compare
      </v-btn>

      <v-text-field
        v-model.number="sandbox.iterations"
        type="number"
        label="N"
        density="compact"
        variant="outlined"
        hide-details
        min="1"
        max="10000"
        class="iterations-input"
      />
    </div>

    <div class="action-right">
      <v-fade-transition>
        <span v-if="benchmarkError" class="feedback-msg text-body-2 text-error">
          {{ benchmarkError }}
        </span>
        <span v-else-if="feedbackMsg" class="feedback-msg text-body-2 text-medium-emphasis">
          {{ feedbackMsg }}
        </span>
      </v-fade-transition>

      <v-btn
        size="small"
        variant="text"
        :loading="isSaving"
        @click="share"
      >
        Share
      </v-btn>

      <v-btn
        size="small"
        variant="tonal"
        :loading="isSaving"
        @click="save"
      >
        Save
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useSandboxStore } from '@/stores/sandbox-store'
import { useBenchmark } from '@/composables/use-benchmark'
import { useSandboxShare } from '@/composables/use-sandbox-share'

const sandbox = useSandboxStore()
const { isRunning, benchmarkError, runBenchmark } = useBenchmark()
const { isSaving, feedbackMsg, save, share } = useSandboxShare()
</script>

<style scoped>
.action-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 12px;
  gap: 8px;
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  flex-shrink: 0;
}

.action-left,
.action-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.iterations-input {
  width: 88px;
  flex-shrink: 0;
}

.feedback-msg {
  font-size: 0.8rem;
}
</style>
