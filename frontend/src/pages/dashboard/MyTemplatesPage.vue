<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTemplatesStore } from '@/stores/templates-store'
import { useSandboxStore } from '@/stores/sandbox-store'
import { useEnginesStore } from '@/stores/engines-store'
import { useStatePersistence } from '@/composables/use-state-persistence'
import type { Template } from '@/types'
import PageShell from '@/components/common/PageShell.vue'
import TemplateList from '@/components/common/TemplateList.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const router = useRouter()
const store = useTemplatesStore()
const sandbox = useSandboxStore()
const engines = useEnginesStore()
const { skipNextRestore } = useStatePersistence()

const deleteTarget = ref<Template | null>(null)
const actionError = ref<string | null>(null)
const searchQuery = ref('')
const selectedEngine = ref<string | null>(null)
const publicityLoadingId = ref<string | null>(null)

onMounted(() => store.fetchMyTemplates())

const filteredTemplates = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  return store.templates
    .filter(tpl => !selectedEngine.value || tpl.engineId === selectedEngine.value)
    .filter(tpl => {
      if (!query) return true
      return tpl.name.toLowerCase().includes(query) || tpl.description?.toLowerCase().includes(query)
    })
    .sort((a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime())
})

const publicTemplates = computed(() => store.templates.filter(tpl => tpl.isPublic).length)
const privateTemplates = computed(() => store.templates.length - publicTemplates.value)
const latestTemplate = computed(() => filteredTemplates.value[0] ?? null)

function engineColor(id: string) {
  const map: Record<string, string> = { handlebars: 'orange', pug: 'teal', ejs: 'deep-purple' }
  return map[id] ?? 'primary'
}

function formatDate(iso?: string) {
  if (!iso) return '-'
  return new Date(iso).toLocaleDateString()
}

async function openInSandbox(tpl: Template) {
  skipNextRestore()
  sandbox.setActiveSlotTemplate(tpl.engineId, tpl.code)
  if (tpl.json) sandbox.json = tpl.json
  sandbox.markDirty()
  await router.push('/sandbox')
}

async function cloneTemplate(tpl: Template) {
  actionError.value = null
  try {
    await store.cloneTemplate(tpl)
  } catch {
    actionError.value = 'Ошибка клонирования.'
  }
}

async function updateTemplatePublicity(tpl: Template, isPublic: boolean) {
  if (tpl.isPublic === isPublic || publicityLoadingId.value) return
  actionError.value = null
  publicityLoadingId.value = tpl.id
  try {
    await store.updateTemplate(tpl.id, { isPublic })
  } catch {
    actionError.value = 'Ошибка изменения публичности.'
  } finally {
    publicityLoadingId.value = null
  }
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  actionError.value = null
  try {
    await store.deleteTemplate(deleteTarget.value.id)
  } catch {
    actionError.value = 'Ошибка удаления.'
  } finally {
    deleteTarget.value = null
  }
}
</script>

<template>
  <PageShell class="templates-page" max-width="1180">
    <template #header>
      <div class="templates-header">
        <div>
          <div class="text-h5 font-weight-bold">Мои шаблоны</div>
          <div class="text-body-2 text-medium-emphasis mt-1">
            Управление сохранёнными шаблонами для sandbox и benchmark-запусков.
          </div>
        </div>

        <div class="header-actions">
          <v-btn :to="{ name: 'sandbox' }" color="primary" variant="tonal">Создать в Sandbox</v-btn>
          <v-btn :to="{ name: 'templates' }" variant="text">Библиотека</v-btn>
        </div>
      </div>
    </template>

    <v-alert v-if="actionError" type="error" variant="tonal" class="mb-4" closable @click:close="actionError = null">
      {{ actionError }}
    </v-alert>

    <v-row class="summary-row" dense>
      <v-col cols="12" sm="4">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Всего шаблонов</div>
            <div class="summary-value">{{ store.templates.length }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Публичные / личные</div>
            <div class="summary-value">{{ publicTemplates }} / {{ privateTemplates }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Последнее обновление</div>
            <div class="summary-value compact">{{ formatDate(latestTemplate?.updatedAt) }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card variant="outlined" class="templates-toolbar">
      <v-text-field
        v-model="searchQuery"
        placeholder="Поиск по названию или описанию"
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
    </v-card>

    <div class="section-header">
      <div>
        <div class="text-subtitle-1 font-weight-medium">Список шаблонов</div>
        <div class="text-body-2 text-medium-emphasis">
          Показано {{ filteredTemplates.length }} из {{ store.templates.length }}
        </div>
      </div>
    </div>

    <TemplateList
      :templates="filteredTemplates"
      :loading="store.loading"
      show-open
      show-clone
      show-delete
      show-public-toggle
      :public-loading-id="publicityLoadingId"
      @open="openInSandbox"
      @clone="cloneTemplate"
      @delete="deleteTarget = $event"
      @public-toggle="updateTemplatePublicity"
    >
      <template #empty>
        <template v-if="store.templates.length === 0">
          <p class="mb-3">Пока нет шаблонов.</p>
          <v-btn :to="{ name: 'sandbox' }" color="primary" variant="tonal">Создать в Sandbox</v-btn>
        </template>
        <template v-else>
          <p class="mb-3">По текущим фильтрам ничего не найдено.</p>
          <v-btn variant="tonal" @click="searchQuery = ''; selectedEngine = null">Сбросить фильтры</v-btn>
        </template>
      </template>
    </TemplateList>

    <ConfirmDialog
      :open="!!deleteTarget"
      title="Удалить шаблон"
      :message="`Удалить «${deleteTarget?.name}»? Это действие необратимо.`"
      @confirm="confirmDelete"
      @cancel="deleteTarget = null"
    />
    <template #footer>
      <div>Публичность шаблонов можно менять здесь без повторного сохранения из sandbox.</div>
    </template>
  </PageShell>
</template>

<style scoped>
.templates-header {
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

.summary-row {
  margin-bottom: 12px;
}

.summary-card {
  height: 100%;
}

.summary-label {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 0.82rem;
}

.summary-value {
  margin-top: 8px;
  font-size: 2rem;
  line-height: 1;
  font-weight: 700;
}

.summary-value.compact {
  font-size: 1.4rem;
}

.templates-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  margin-bottom: 16px;
}

.search-field {
  min-width: 260px;
  flex: 1 1 320px;
}

.engine-filters {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.section-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

@media (max-width: 760px) {
  .templates-header,
  .templates-toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .header-actions,
  .engine-filters {
    justify-content: flex-start;
  }

  .search-field {
    min-width: 0;
  }
}
</style>
