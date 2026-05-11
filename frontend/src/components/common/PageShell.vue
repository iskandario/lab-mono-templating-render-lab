<script setup lang="ts">
import { computed, useSlots } from 'vue'

const props = withDefaults(defineProps<{
  maxWidth?: number | string
  fullWidth?: boolean
  flush?: boolean
}>(), {
  maxWidth: 1180,
  fullWidth: false,
  flush: false,
})

const slots = useSlots()

const shellStyle = computed(() => {
  if (props.fullWidth) return undefined

  const maxWidth = typeof props.maxWidth === 'number'
    ? `${props.maxWidth}px`
    : props.maxWidth

  return { '--page-shell-max-width': maxWidth }
})
</script>

<template>
  <section
    class="page-shell"
    :class="{ 'page-shell--full-width': fullWidth, 'page-shell--flush': flush }"
    :style="shellStyle"
  >
    <header v-if="slots.header" class="page-shell__header">
      <slot name="header" />
    </header>

    <main class="page-shell__main">
      <slot />
    </main>

    <footer v-if="slots.footer" class="page-shell__footer">
      <slot name="footer" />
    </footer>
  </section>
</template>

<style scoped>
.page-shell {
  width: 100%;
  max-width: var(--page-shell-max-width, 1180px);
  margin: 0 auto;
  padding: 24px 16px 32px;
}

.page-shell--full-width {
  max-width: none;
}

.page-shell--flush {
  padding: 0;
}

.page-shell__header {
  margin-bottom: 20px;
}

.page-shell__main {
  min-width: 0;
}

.page-shell__footer {
  margin-top: 20px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 0.82rem;
}

@media (min-width: 600px) {
  .page-shell {
    padding-right: 24px;
    padding-left: 24px;
  }

  .page-shell--flush {
    padding: 0;
  }
}
</style>
