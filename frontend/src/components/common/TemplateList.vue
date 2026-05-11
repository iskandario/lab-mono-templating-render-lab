<script setup lang="ts">
import type { Template } from '@/types'
import TemplateCard from './TemplateCard.vue'

defineProps<{
  templates: Template[]
  loading?: boolean
  showOpen?: boolean
  showClone?: boolean
  showDelete?: boolean
  showPublicToggle?: boolean
  publicLoadingId?: string | null
}>()

const emit = defineEmits<{
  open: [template: Template]
  clone: [template: Template]
  delete: [template: Template]
  'public-toggle': [template: Template, isPublic: boolean]
}>()
</script>

<template>
  <div v-if="loading" class="d-flex flex-column gap-3">
    <v-skeleton-loader v-for="n in 3" :key="n" type="card" />
  </div>

  <div v-else-if="templates.length === 0" class="text-center py-12 text-medium-emphasis">
    <slot name="empty">
      <p>Пока нет шаблонов.</p>
    </slot>
  </div>

  <div v-else class="d-flex flex-column gap-3">
    <TemplateCard
      v-for="tpl in templates"
      :key="tpl.id"
      :template="tpl"
      :show-open="showOpen"
      :show-clone="showClone"
      :show-delete="showDelete"
      :show-public-toggle="showPublicToggle"
      :public-loading="publicLoadingId === tpl.id"
      @open="emit('open', $event)"
      @clone="emit('clone', $event)"
      @delete="emit('delete', $event)"
      @public-toggle="(template, isPublic) => emit('public-toggle', template, isPublic)"
    />
  </div>
</template>
