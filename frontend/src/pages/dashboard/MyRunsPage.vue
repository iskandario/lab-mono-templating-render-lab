<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRenderRunsStore } from '@/stores/render-runs-store'
import { useEnginesStore } from '@/stores/engines-store'
import PageShell from '@/components/common/PageShell.vue'
import type { RenderRun } from '@/types'
import {
  formatRunBytes,
  formatRunDate,
  formatRunMs,
  hasRunMetrics,
  runStatusColor,
  runStatusLabel,
} from '@/utils/render-run-format'

const store = useRenderRunsStore()
const engines = useEnginesStore()

const selectedEngine = ref<string | null>(null)
const selectedStatus = ref<string | null>(null)
const metricMode = ref<'all' | 'with_metrics' | 'without_metrics'>('all')
const sortBy = ref<'newest' | 'oldest' | 'avg_asc' | 'p95_asc' | 'iterations_desc'>('newest')
const searchQuery = ref('')

onMounted(() => store.fetchRuns())

const engineOptions = computed(() => {
  const ids = new Set(store.runs.map(run => run.engineId))
  return engines.engines.filter(engine => ids.has(engine.id))
})

const filteredRuns = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  return store.runs
    .filter(run => !selectedEngine.value || run.engineId === selectedEngine.value)
    .filter(run => !selectedStatus.value || run.status === selectedStatus.value)
    .filter(run => {
      if (metricMode.value === 'with_metrics') return hasRunMetrics(run)
      if (metricMode.value === 'without_metrics') return !hasRunMetrics(run)
      return true
    })
    .filter(run => {
      if (!query) return true
      return run.id.toLowerCase().includes(query)
        || run.engineId.toLowerCase().includes(query)
        || run.status.toLowerCase().includes(query)
        || (run.templateId?.toLowerCase().includes(query) ?? false)
    })
    .sort(compareRuns)
})

const successfulRuns = computed(() => store.runs.filter(hasRunMetrics))
const failedRuns = computed(() => store.runs.filter(run => run.status === 'failure').length)
const inProgressRuns = computed(() => store.runs.filter(run => run.status === 'in_progress').length)
const successRate = computed(() => {
  if (store.runs.length === 0) return null
  return Math.round((successfulRuns.value.length / store.runs.length) * 100)
})
const averageAvgMs = computed(() => average(successfulRuns.value.map(run => run.avgMs)))
const bestAvgMs = computed(() => min(successfulRuns.value.map(run => run.avgMs)))
const bestP95Ms = computed(() => min(successfulRuns.value.map(run => run.p95Ms)))
const latestRun = computed(() => [...store.runs].sort((a, b) => timestamp(b) - timestamp(a))[0] ?? null)

function compareRuns(a: RenderRun, b: RenderRun): number {
  if (sortBy.value === 'oldest') return timestamp(a) - timestamp(b)
  if (sortBy.value === 'avg_asc') return nullableMetric(a.avgMs) - nullableMetric(b.avgMs)
  if (sortBy.value === 'p95_asc') return nullableMetric(a.p95Ms) - nullableMetric(b.p95Ms)
  if (sortBy.value === 'iterations_desc') return b.iterations - a.iterations

  return timestamp(b) - timestamp(a)
}

function timestamp(run: RenderRun): number {
  return new Date(run.createdAt).getTime()
}

function nullableMetric(value: number | null): number {
  return value === null ? Number.POSITIVE_INFINITY : value
}

function average(values: Array<number | null>): number | null {
  const numeric = values.filter((value): value is number => value !== null)
  if (numeric.length === 0) return null

  return numeric.reduce((acc, value) => acc + value, 0) / numeric.length
}

function min(values: Array<number | null>): number | null {
  const numeric = values.filter((value): value is number => value !== null)
  if (numeric.length === 0) return null

  return Math.min(...numeric)
}

function engineColor(id: string) {
  const map: Record<string, string> = { handlebars: 'orange', pug: 'teal', ejs: 'deep-purple' }
  return map[id] ?? 'primary'
}

function shortId(id: string): string {
  return id.slice(0, 8)
}

function resetFilters() {
  selectedEngine.value = null
  selectedStatus.value = null
  metricMode.value = 'all'
  sortBy.value = 'newest'
  searchQuery.value = ''
}
</script>

<template>
  <PageShell class="runs-page" max-width="1180">
    <template #header>
      <div class="runs-header">
        <div>
          <div class="text-h5 font-weight-bold">Мои запуски</div>
          <div class="text-body-2 text-medium-emphasis mt-1">
            История benchmark-запусков с сохранёнными метриками по движкам.
          </div>
        </div>

        <div class="header-actions">
          <v-btn :to="{ name: 'sandbox' }" color="primary" variant="tonal">Запустить benchmark</v-btn>
          <v-btn :to="{ name: 'dashboard' }" variant="text">Dashboard</v-btn>
        </div>
      </div>
    </template>

    <v-row class="summary-row" dense>
      <v-col cols="12" sm="6" lg="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Всего запусков</div>
            <div class="summary-value">{{ store.runs.length }}</div>
            <div class="summary-caption">{{ inProgressRuns }} в процессе</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Успешность</div>
            <div class="summary-value">{{ successRate === null ? '-' : `${successRate}%` }}</div>
            <div class="summary-caption">{{ failedRuns }} ошибок</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Среднее avg</div>
            <div class="summary-value compact">{{ averageAvgMs === null ? '-' : formatRunMs(averageAvgMs) }}</div>
            <div class="summary-caption">По успешным запускам</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Лучший avg / p95</div>
            <div class="summary-value compact">
              {{ bestAvgMs === null ? '-' : formatRunMs(bestAvgMs) }}
            </div>
            <div class="summary-caption">{{ bestP95Ms === null ? '-' : `p95 ${formatRunMs(bestP95Ms)}` }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card variant="outlined" class="runs-toolbar">
      <v-text-field
        v-model="searchQuery"
        placeholder="Поиск по id, template id, движку или статусу"
        density="compact"
        variant="outlined"
        prepend-inner-icon="mdi-magnify"
        hide-details
        clearable
        class="search-field"
      />

      <div class="filter-row">
        <v-chip
          :variant="selectedEngine === null ? 'tonal' : 'outlined'"
          color="primary"
          size="small"
          @click="selectedEngine = null"
        >
          Все движки
        </v-chip>
        <v-chip
          v-for="engine in engineOptions"
          :key="engine.id"
          :variant="selectedEngine === engine.id ? 'tonal' : 'outlined'"
          :color="engineColor(engine.id)"
          size="small"
          @click="selectedEngine = selectedEngine === engine.id ? null : engine.id"
        >
          {{ engine.name }}
        </v-chip>
      </div>

      <div class="toolbar-controls">
        <v-select
          v-model="selectedStatus"
          :items="[
            { title: 'Все статусы', value: null },
            { title: 'Успешно', value: 'success' },
            { title: 'Ошибка', value: 'failure' },
            { title: 'В процессе', value: 'in_progress' },
          ]"
          label="Статус"
          density="compact"
          variant="outlined"
          hide-details
          class="control-field"
        />

        <v-select
          v-model="metricMode"
          :items="[
            { title: 'Все метрики', value: 'all' },
            { title: 'С метриками', value: 'with_metrics' },
            { title: 'Без метрик', value: 'without_metrics' },
          ]"
          label="Метрики"
          density="compact"
          variant="outlined"
          hide-details
          class="control-field"
        />

        <v-select
          v-model="sortBy"
          :items="[
            { title: 'Сначала новые', value: 'newest' },
            { title: 'Сначала старые', value: 'oldest' },
            { title: 'Лучший avg', value: 'avg_asc' },
            { title: 'Лучший p95', value: 'p95_asc' },
            { title: 'Больше итераций', value: 'iterations_desc' },
          ]"
          label="Сортировка"
          density="compact"
          variant="outlined"
          hide-details
          class="control-field"
        />

        <v-btn variant="text" size="small" @click="resetFilters">Сбросить</v-btn>
      </div>
    </v-card>

    <v-alert v-if="store.error" type="error" variant="tonal" class="mb-4">
      {{ store.error }}
      <template #append>
        <v-btn size="small" variant="text" @click="store.fetchRuns()">Повторить</v-btn>
      </template>
    </v-alert>

    <v-card v-if="store.loading" variant="outlined">
      <v-skeleton-loader type="table-heading, table-row@6" />
    </v-card>

    <v-card v-else-if="store.runs.length === 0" variant="outlined">
      <v-card-text class="empty-state">
        <p class="mb-3">Пока нет запусков.</p>
        <v-btn :to="{ name: 'sandbox' }" color="primary" variant="tonal">Запустить benchmark</v-btn>
      </v-card-text>
    </v-card>

    <v-card v-else-if="filteredRuns.length === 0" variant="outlined">
      <v-card-text class="empty-state">
        <p class="mb-3">По текущим фильтрам запусков нет.</p>
        <v-btn variant="tonal" @click="resetFilters">Сбросить фильтры</v-btn>
      </v-card-text>
    </v-card>

    <v-card v-else variant="outlined" class="runs-table-card">
      <div class="table-header">
        <div>
          <div class="text-subtitle-1 font-weight-medium">История запусков</div>
          <div class="text-body-2 text-medium-emphasis">
            Показано {{ filteredRuns.length }} из {{ store.runs.length }}
          </div>
        </div>

        <div v-if="latestRun" class="text-body-2 text-medium-emphasis">
          Последний: {{ formatRunDate(latestRun.createdAt) }}
        </div>
      </div>

      <v-table density="compact" class="runs-table">
        <thead>
          <tr>
            <th>Запуск</th>
            <th>Движок</th>
            <th>Статус</th>
            <th class="metric-cell">N</th>
            <th class="metric-cell">Avg</th>
            <th class="metric-cell">Min</th>
            <th class="metric-cell">Max</th>
            <th class="metric-cell">P95</th>
            <th class="metric-cell">Размер</th>
            <th class="date-cell">Дата</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="run in filteredRuns" :key="run.id">
            <td>
              <div class="run-id">#{{ shortId(run.id) }}</div>
              <div v-if="run.templateId" class="template-id">tpl {{ shortId(run.templateId) }}</div>
              <div v-else class="template-id">snapshot</div>
            </td>
            <td>
              <v-chip size="x-small" :color="engineColor(run.engineId)" variant="tonal" class="text-uppercase">
                {{ run.engineId }}
              </v-chip>
            </td>
            <td>
              <v-chip size="x-small" :color="runStatusColor(run.status)" variant="tonal">
                {{ runStatusLabel(run.status) }}
              </v-chip>
            </td>
            <td class="metric-cell">{{ run.iterations }}</td>
            <template v-if="hasRunMetrics(run)">
              <td class="metric-cell">{{ formatRunMs(run.avgMs) }}</td>
              <td class="metric-cell">{{ formatRunMs(run.minMs) }}</td>
              <td class="metric-cell">{{ formatRunMs(run.maxMs) }}</td>
              <td class="metric-cell">{{ formatRunMs(run.p95Ms) }}</td>
              <td class="metric-cell">{{ formatRunBytes(run.outputBytes) }}</td>
            </template>
            <template v-else>
              <td colspan="5" class="text-medium-emphasis">Метрики пока не записаны</td>
            </template>
            <td class="date-cell">{{ formatRunDate(run.createdAt) }}</td>
          </tr>
        </tbody>
      </v-table>
    </v-card>
    <template #footer>
      <div>История хранит сохранённые результаты benchmark и помогает быстро сверить метрики после изменений.</div>
    </template>
  </PageShell>
</template>

<style scoped>
.runs-header {
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

.summary-label,
.summary-caption,
.template-id {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.summary-label {
  font-size: 0.82rem;
}

.summary-value {
  margin-top: 8px;
  font-size: 2rem;
  line-height: 1;
  font-weight: 700;
}

.summary-value.compact {
  font-size: 1.35rem;
}

.summary-caption {
  margin-top: 6px;
  font-size: 0.78rem;
}

.runs-toolbar {
  display: grid;
  gap: 12px;
  padding: 12px;
  margin-bottom: 16px;
}

.search-field {
  max-width: 520px;
}

.filter-row,
.toolbar-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.control-field {
  width: 180px;
}

.empty-state {
  padding: 42px 16px;
  text-align: center;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.runs-table-card {
  overflow: hidden;
}

.table-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px 8px;
}

.runs-table {
  min-width: 980px;
}

.runs-table-card :deep(.v-table__wrapper) {
  overflow-x: auto;
}

.metric-cell {
  text-align: right;
  white-space: nowrap;
}

.date-cell {
  white-space: nowrap;
}

.run-id {
  font-weight: 600;
}

.template-id {
  margin-top: 2px;
  font-size: 0.75rem;
}

@media (max-width: 760px) {
  .runs-header,
  .table-header {
    align-items: stretch;
    flex-direction: column;
  }

  .header-actions,
  .toolbar-controls {
    justify-content: flex-start;
  }

  .search-field,
  .control-field {
    width: 100%;
    max-width: none;
  }
}
</style>
