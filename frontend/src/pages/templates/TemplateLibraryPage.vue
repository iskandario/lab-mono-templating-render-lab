<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTemplatesStore } from '@/stores/templates-store'
import { useSandboxStore } from '@/stores/sandbox-store'
import { useAuthStore } from '@/stores/auth-store'
import { useEnginesStore } from '@/stores/engines-store'
import type { Template } from '@/types'
import PageShell from '@/components/common/PageShell.vue'
import TemplateList from '@/components/common/TemplateList.vue'
import { useStatePersistence } from '@/composables/use-state-persistence'

interface Preset {
  engineId: string
  name: string
  description: string
  code: string
  json: string
}

type SortMode = 'updated_desc' | 'created_desc' | 'name_asc' | 'engine_asc'

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
const sortMode = ref<SortMode>('updated_desc')
const snackbar = ref(false)
const snackbarMsg = ref('')
let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(searchQuery, val => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    debouncedQuery.value = val
  }, 250)
})

const engineCounts = computed(() => {
  return store.templates.reduce<Record<string, number>>((acc, template) => {
    acc[template.engineId] = (acc[template.engineId] ?? 0) + 1
    return acc
  }, {})
})

const filteredTemplates = computed(() => {
  const query = debouncedQuery.value.trim().toLowerCase()

  return store.templates
    .filter(template => !selectedEngine.value || template.engineId === selectedEngine.value)
    .filter(template => {
      if (!query) return true
      return template.name.toLowerCase().includes(query)
        || template.engineId.toLowerCase().includes(query)
        || (template.description?.toLowerCase().includes(query) ?? false)
    })
    .sort(compareTemplates)
})

const activeFiltersCount = computed(() => Number(!!selectedEngine.value) + Number(!!debouncedQuery.value.trim()))
const hasTemplates = computed(() => store.templates.length > 0)
const emptyText = computed(() => {
  if (!hasTemplates.value) return 'Публичных шаблонов пока нет.'
  return 'По текущим фильтрам шаблоны не найдены.'
})

onMounted(() => store.fetchPublicTemplates())

function compareTemplates(a: Template, b: Template): number {
  if (sortMode.value === 'name_asc') return a.name.localeCompare(b.name)
  if (sortMode.value === 'engine_asc') return a.engineId.localeCompare(b.engineId) || a.name.localeCompare(b.name)
  if (sortMode.value === 'created_desc') return dateValue(b.createdAt) - dateValue(a.createdAt)

  return dateValue(b.updatedAt) - dateValue(a.updatedAt)
}

function dateValue(value?: string): number {
  return value ? new Date(value).getTime() : 0
}

function engineColor(id: string) {
  const map: Record<string, string> = { handlebars: 'orange', pug: 'teal', ejs: 'deep-purple' }
  return map[id] ?? 'primary'
}

function loadPreset(preset: Preset) {
  skipNextRestore()
  sandbox.setActiveSlotTemplate(preset.engineId, preset.code)
  sandbox.json = preset.json
  sandbox.markDirty()
  router.push('/sandbox')
}

function useInSandbox(tpl: Template) {
  skipNextRestore()
  sandbox.setActiveSlotTemplate(tpl.engineId, tpl.code)
  if (tpl.json) sandbox.json = tpl.json
  sandbox.markDirty()
  router.push('/sandbox')
}

async function cloneTemplate(tpl: Template) {
  if (!auth.isAuthenticated) {
    showSnackbar('Войдите, чтобы клонировать шаблоны')
    return
  }
  try {
    await store.cloneTemplate(tpl)
    showSnackbar('Шаблон скопирован в «Мои шаблоны»')
  } catch {
    showSnackbar('Ошибка клонирования')
  }
}

function resetFilters() {
  searchQuery.value = ''
  debouncedQuery.value = ''
  selectedEngine.value = null
  sortMode.value = 'updated_desc'
}

function showSnackbar(msg: string) {
  snackbarMsg.value = msg
  snackbar.value = true
}
</script>

<template>
  <PageShell class="templates-page" max-width="1180">
    <template #header>
      <div class="templates-header">
        <div>
          <div class="text-h5 font-weight-bold">Библиотека шаблонов</div>
          <div class="text-body-2 text-medium-emphasis mt-1">
            Публичные шаблоны и стартовые заготовки для sandbox.
          </div>
        </div>

        <div class="header-actions">
          <v-btn :to="{ name: 'sandbox' }" color="primary" variant="tonal">Открыть Sandbox</v-btn>
          <v-btn v-if="auth.isAuthenticated" :to="{ name: 'dashboard-templates' }" variant="text">Мои шаблоны</v-btn>
        </div>
      </div>
    </template>

    <section class="quick-start-section">
      <div class="section-header">
        <div>
          <div class="text-subtitle-1 font-weight-medium">Быстрый старт</div>
          <div class="text-body-2 text-medium-emphasis">Загрузит пример в текущий слот sandbox.</div>
        </div>
      </div>

      <div class="quick-start-grid">
        <v-card
          v-for="preset in QUICK_START_PRESETS"
          :key="preset.engineId"
          variant="outlined"
          class="quick-start-card"
        >
          <div class="quick-start-title">
            <span class="quick-start-name">{{ preset.name }}</span>
            <v-chip
              size="x-small"
              :color="engineColor(preset.engineId)"
              variant="tonal"
              class="quick-start-engine-chip text-uppercase"
            >
              {{ preset.engineId }}
            </v-chip>
          </div>
          <div class="quick-start-description">{{ preset.description }}</div>
          <v-btn size="small" variant="tonal" color="primary" class="quick-start-action" @click="loadPreset(preset)">
            Открыть в Sandbox
          </v-btn>
        </v-card>
      </div>
    </section>

    <section>
      <div class="section-header public-header">
        <div>
          <div class="text-subtitle-1 font-weight-medium">Публичные шаблоны</div>
          <div class="text-body-2 text-medium-emphasis">
            Показано {{ filteredTemplates.length }} из {{ store.templates.length }}
          </div>
        </div>

        <v-chip v-if="activeFiltersCount" size="small" color="primary" variant="tonal">
          {{ activeFiltersCount }} фильтр
        </v-chip>
      </div>

      <v-card variant="outlined" class="filter-panel">
        <v-text-field
          v-model="searchQuery"
          placeholder="Поиск по названию, описанию или движку"
          density="compact"
          variant="outlined"
          hide-details
          clearable
          prepend-inner-icon="mdi-magnify"
          class="search-field"
        />

        <div class="engine-filters">
          <v-chip
            :variant="selectedEngine === null ? 'tonal' : 'outlined'"
            color="primary"
            size="small"
            @click="selectedEngine = null"
          >
            Все
            <span class="chip-count">{{ store.templates.length }}</span>
          </v-chip>
          <v-chip
            v-for="engine in engines.engines"
            :key="engine.id"
            :variant="selectedEngine === engine.id ? 'tonal' : 'outlined'"
            :color="engineColor(engine.id)"
            size="small"
            :disabled="!engineCounts[engine.id]"
            @click="selectedEngine = selectedEngine === engine.id ? null : engine.id"
          >
            {{ engine.name }}
            <span class="chip-count">{{ engineCounts[engine.id] ?? 0 }}</span>
          </v-chip>
        </div>

        <div class="filter-actions">
          <v-select
            v-model="sortMode"
            :items="[
              { title: 'Сначала обновленные', value: 'updated_desc' },
              { title: 'Сначала новые', value: 'created_desc' },
              { title: 'По названию', value: 'name_asc' },
              { title: 'По движку', value: 'engine_asc' },
            ]"
            label="Сортировка"
            density="compact"
            variant="outlined"
            hide-details
            class="sort-field"
          />
          <v-btn size="small" variant="text" :disabled="activeFiltersCount === 0 && sortMode === 'updated_desc'" @click="resetFilters">
            Сбросить
          </v-btn>
        </div>
      </v-card>

      <TemplateList
        :templates="filteredTemplates"
        :loading="store.loading"
        show-open
        show-clone
        @open="useInSandbox"
        @clone="cloneTemplate"
      >
        <template #empty>
          <p class="mb-3">{{ emptyText }}</p>
          <v-btn v-if="hasTemplates" variant="tonal" @click="resetFilters">Сбросить фильтры</v-btn>
          <v-btn v-else :to="{ name: 'sandbox' }" color="primary" variant="tonal">Создать в Sandbox</v-btn>
        </template>
      </TemplateList>
    </section>

    <v-snackbar v-model="snackbar" :timeout="3000" location="bottom right">
      {{ snackbarMsg }}
    </v-snackbar>

    <template #footer>
      <div>Библиотека показывает только публичные шаблоны, а быстрый старт загружает пример в активный слот sandbox.</div>
    </template>
  </PageShell>
</template>

<style scoped>
.templates-header,
.section-header,
.public-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.header-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  flex-wrap: wrap;
}

.quick-start-section {
  margin-bottom: 24px;
}

.section-header {
  margin-bottom: 12px;
}

.quick-start-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.quick-start-card {
  display: flex;
  flex-direction: column;
  min-height: 132px;
  padding: 12px;
}

.quick-start-title {
  display: grid;
  grid-template-columns: minmax(0, 1fr) max-content;
  align-items: start;
  column-gap: 8px;
  min-width: 0;
}

.quick-start-name {
  min-width: 0;
  line-height: 1.35;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.quick-start-engine-chip {
  justify-self: end;
  max-width: 100%;
}

.quick-start-engine-chip :deep(.v-chip__content) {
  min-width: 0;
  overflow: visible;
  white-space: nowrap;
}

.quick-start-description {
  margin-top: 8px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 0.85rem;
  line-height: 1.35;
}

.quick-start-action {
  align-self: flex-start;
  margin-top: auto;
}

.filter-panel {
  display: grid;
  grid-template-columns: minmax(260px, 1fr) auto;
  gap: 12px;
  align-items: center;
  padding: 12px;
  margin-bottom: 14px;
}

.search-field {
  min-width: 0;
}

.engine-filters {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.chip-count {
  margin-left: 6px;
  opacity: 0.72;
}

.filter-actions {
  display: flex;
  grid-column: 1 / -1;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
}

.sort-field {
  width: 220px;
}

@media (max-width: 900px) {
  .quick-start-grid {
    grid-template-columns: 1fr;
  }

  .filter-panel {
    grid-template-columns: 1fr;
  }

  .engine-filters,
  .filter-actions {
    justify-content: flex-start;
  }
}

@media (max-width: 760px) {
  .templates-header,
  .section-header,
  .public-header {
    align-items: stretch;
    flex-direction: column;
  }

  .header-actions,
  .filter-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .sort-field {
    width: 100%;
  }
}
</style>
