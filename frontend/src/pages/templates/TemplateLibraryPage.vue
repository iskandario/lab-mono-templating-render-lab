<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTemplatesStore } from '@/stores/templates-store'
import { useSandboxStore } from '@/stores/sandbox-store'
import { useAuthStore } from '@/stores/auth-store'
import { useEnginesStore } from '@/stores/engines-store'
import type { Template } from '@/types'
import TemplateList from '@/components/common/TemplateList.vue'
import { useStatePersistence } from '@/composables/use-state-persistence'

interface Preset {
  engineId: string
  name: string
  description: string
  code: string
  json: string
}

const QUICK_START_PRESETS: Preset[] = [
  {
    engineId: 'handlebars',
    name: 'Handlebars Hello',
    description: 'Приветствие с условиями',
    code: '<h1>Hello, {{name}}!</h1>\n{{#if isGuest}}<p>Welcome, guest!</p>{{/if}}',
    json: '{"name":"World","isGuest":false}',
  },
  {
    engineId: 'pug',
    name: 'Pug Hello',
    description: 'Минимальный Pug-шаблон с циклом',
    code: 'h1 Hello, #{name}!\nul\n  each item in items\n    li= item',
    json: '{"name":"World","items":["Alpha","Beta","Gamma"]}',
  },
  {
    engineId: 'ejs',
    name: 'EJS Hello',
    description: 'EJS-шаблон с перебором',
    code: '<h1>Hello, <%= name %>!</h1>\n<ul>\n<% items.forEach(item => { %>\n  <li><%= item %></li>\n<% }) %>\n</ul>',
    json: '{"name":"World","items":["Alpha","Beta","Gamma"]}',
  },
]

const router = useRouter()
const store = useTemplatesStore()
const sandbox = useSandboxStore()
const auth = useAuthStore()
const engines = useEnginesStore()
const { skipNextRestore } = useStatePersistence()

const searchQuery = ref('')
const debouncedQuery = ref('')
const selectedEngine = ref<string | null>(null)
const snackbar = ref(false)
const snackbarMsg = ref('')
let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(searchQuery, val => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    debouncedQuery.value = val
  }, 300)
})

const filteredTemplates = computed(() => {
  let list = store.templates
  if (selectedEngine.value) list = list.filter(t => t.engineId === selectedEngine.value)
  if (debouncedQuery.value.trim()) {
    const q = debouncedQuery.value.toLowerCase()
    list = list.filter(t => t.name.toLowerCase().includes(q) || t.description?.toLowerCase().includes(q))
  }
  return list
})

onMounted(() => store.fetchPublicTemplates())

function engineColor(id: string) {
  const map: Record<string, string> = { handlebars: 'orange', pug: 'teal', ejs: 'deep-purple' }
  return map[id] ?? 'primary'
}

function loadPreset(preset: Preset) {
  skipNextRestore()
  sandbox.slotA.engineId = preset.engineId
  sandbox.slotA.code = preset.code
  sandbox.slotB.engineId = preset.engineId
  sandbox.slotB.code = preset.code
  sandbox.json = preset.json
  sandbox.markDirty()
  router.push('/sandbox')
}

function useInSandbox(tpl: Template) {
  skipNextRestore()
  sandbox.slotA.engineId = tpl.engineId
  sandbox.slotA.code = tpl.code
  sandbox.slotB.engineId = tpl.engineId
  sandbox.slotB.code = tpl.code
  sandbox.json = tpl.json ?? '{}'
  sandbox.markDirty()
  router.push('/sandbox')
}

async function cloneTemplate(tpl: Template) {
  if (!auth.isAuthenticated) {
    showSnackbar('Войдите, чтобы клонировать шаблоны')
    return
  }
  try {
    await store.cloneTemplate(tpl.id)
    showSnackbar('Шаблон скопирован в «Мои шаблоны»')
  } catch {
    showSnackbar('Ошибка клонирования')
  }
}

function showSnackbar(msg: string) {
  snackbarMsg.value = msg
  snackbar.value = true
}
</script>

<template>
  <v-container max-width="860">
    <div class="text-h5 font-weight-bold mb-6">Библиотека шаблонов</div>

    <!-- Quick Start -->
    <div class="mb-8">
      <div class="text-subtitle-1 font-weight-medium mb-3">Быстрый старт</div>
      <v-row dense>
        <v-col v-for="preset in QUICK_START_PRESETS" :key="preset.engineId" cols="12" sm="4">
          <v-card variant="outlined" class="h-100">
            <v-card-title class="d-flex align-center gap-2 pt-3 pb-1 text-body-1 font-weight-medium">
              {{ preset.name }}
              <v-chip size="x-small" :color="engineColor(preset.engineId)" variant="tonal" class="ml-auto text-uppercase">
                {{ preset.engineId }}
              </v-chip>
            </v-card-title>
            <v-card-text class="text-body-2 text-medium-emphasis pb-2">
              {{ preset.description }}
            </v-card-text>
            <v-card-actions>
              <v-btn size="small" variant="tonal" color="primary" @click="loadPreset(preset)">
                Открыть в Sandbox
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Public Templates -->
    <div class="text-subtitle-1 font-weight-medium mb-3">Публичные шаблоны</div>

    <!-- Search + Filter -->
    <div class="d-flex flex-wrap gap-3 align-center mb-4">
      <v-text-field
        v-model="searchQuery"
        placeholder="Поиск шаблонов…"
        density="compact"
        variant="outlined"
        hide-details
        clearable
        prepend-inner-icon="mdi-magnify"
        class="search-field"
      />
      <div class="d-flex gap-2 flex-wrap">
        <v-chip
          :variant="selectedEngine === null ? 'tonal' : 'outlined'"
          color="primary"
          size="small"
          @click="selectedEngine = null"
        >
          Все
        </v-chip>
        <v-chip
          v-for="engine in engines.engines"
          :key="engine.id"
          :variant="selectedEngine === engine.id ? 'tonal' : 'outlined'"
          :color="engineColor(engine.id)"
          size="small"
          @click="selectedEngine = selectedEngine === engine.id ? null : engine.id"
        >
          {{ engine.name }}
        </v-chip>
      </div>
    </div>

    <TemplateList
      :templates="filteredTemplates"
      :loading="store.loading"
      show-open
      show-clone
      @open="useInSandbox"
      @clone="cloneTemplate"
    >
      <template #empty>
        <p>Шаблоны не найдены.</p>
      </template>
    </TemplateList>

    <v-snackbar v-model="snackbar" :timeout="3000" location="bottom right">
      {{ snackbarMsg }}
    </v-snackbar>
  </v-container>
</template>

<style scoped>
.search-field {
  min-width: 220px;
  flex: 1 1 220px;
  max-width: 360px;
}
</style>
