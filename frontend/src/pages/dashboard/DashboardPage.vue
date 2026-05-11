<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useTemplatesStore } from '@/stores/templates-store'
import { useRenderRunsStore } from '@/stores/render-runs-store'
import PageShell from '@/components/common/PageShell.vue'
import {
  formatRunBytes,
  formatRunDate,
  formatRunMs,
  hasRunMetrics,
  runStatusColor,
  runStatusLabel,
} from '@/utils/render-run-format'

const templatesStore = useTemplatesStore()
const runsStore = useRenderRunsStore()

onMounted(async () => {
  await Promise.all([templatesStore.fetchMyTemplates(), runsStore.fetchRuns()])
})

const recentRuns = computed(() => runsStore.runs.slice(0, 5))
const completedRuns = computed(() => runsStore.runs.filter(run => run.status === 'success'))
const failedRuns = computed(() => runsStore.runs.filter(run => run.status === 'failure'))
const runsWithMetrics = computed(() => runsStore.runs.filter(hasRunMetrics))
const successRate = computed(() => {
  if (runsStore.runs.length === 0) return null
  return Math.round((completedRuns.value.length / runsStore.runs.length) * 100)
})
const averageRunMs = computed(() => {
  if (runsWithMetrics.value.length === 0) return null
  const total = runsWithMetrics.value.reduce((sum, run) => sum + (run.avgMs ?? 0), 0)
  return total / runsWithMetrics.value.length
})
const latestRun = computed(() => runsStore.runs[0] ?? null)
</script>

<template>
  <PageShell class="dashboard-page" max-width="1180">
    <template #header>
      <div class="dashboard-header">
        <div>
          <div class="text-h5 font-weight-bold">Дашборд</div>
          <div class="text-body-2 text-medium-emphasis mt-1">
            Сводка по шаблонам и сохранённым benchmark-запускам.
          </div>
        </div>
      </div>
    </template>

    <v-row class="summary-row" dense>
      <v-col cols="12" sm="6" lg="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text class="summary-card-body">
            <div class="summary-label">Шаблоны</div>
            <div class="summary-value">{{ templatesStore.templates.length }}</div>
            <v-btn :to="{ name: 'dashboard-templates' }" size="small" variant="text" class="summary-link">
              Мои шаблоны
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" lg="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text class="summary-card-body">
            <div class="summary-label">Запуски</div>
            <div class="summary-value">{{ runsStore.runs.length }}</div>
            <div class="summary-meta">{{ completedRuns.length }} успешных / {{ failedRuns.length }} с ошибкой</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" lg="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text class="summary-card-body">
            <div class="summary-label">Успешность</div>
            <div class="summary-value">{{ successRate === null ? '-' : `${successRate}%` }}</div>
            <div class="summary-meta">По всем сохранённым запускам</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" lg="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text class="summary-card-body">
            <div class="summary-label">Среднее время</div>
            <div class="summary-value">{{ averageRunMs === null ? '-' : formatRunMs(averageRunMs) }}</div>
            <div class="summary-meta">По успешным запускам</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <div class="quick-actions">
      <v-btn :to="{ name: 'sandbox' }" variant="tonal" color="primary" class="quick-action">
        <span class="quick-action-title">Sandbox</span>
        <span class="quick-action-subtitle">Запустить или сравнить шаблоны</span>
      </v-btn>
      <v-btn :to="{ name: 'dashboard-templates' }" variant="tonal" class="quick-action">
        <span class="quick-action-title">Мои шаблоны</span>
        <span class="quick-action-subtitle">Открыть, клонировать, удалить</span>
      </v-btn>
      <v-btn :to="{ name: 'dashboard-runs' }" variant="tonal" class="quick-action">
        <span class="quick-action-title">Мои запуски</span>
        <span class="quick-action-subtitle">Посмотреть benchmark-результаты</span>
      </v-btn>
      <v-btn :to="{ name: 'templates' }" variant="tonal" class="quick-action">
        <span class="quick-action-title">Библиотека</span>
        <span class="quick-action-subtitle">Найти публичный шаблон</span>
      </v-btn>
    </div>

    <v-row dense>
      <v-col cols="12" lg="8">
        <v-card variant="outlined" class="runs-card">
          <div class="section-header">
            <div>
              <div class="text-subtitle-1 font-weight-medium">Последние запуски</div>
              <div class="text-body-2 text-medium-emphasis">Последние 5 сохранённых benchmark-результатов</div>
            </div>
            <v-btn :to="{ name: 'dashboard-runs' }" size="small" variant="text">Открыть все</v-btn>
          </div>

          <v-skeleton-loader v-if="runsStore.loading" type="table-row@5" />

          <div v-else-if="recentRuns.length === 0" class="empty-state">
            <div class="text-body-2 text-medium-emphasis mb-3">Пока нет запусков.</div>
            <v-btn :to="{ name: 'sandbox' }" color="primary" variant="tonal">Запустить бенчмарк</v-btn>
          </div>

          <v-table v-else density="compact" class="runs-table">
            <thead>
              <tr>
                <th>Движок</th>
                <th>Статус</th>
                <th class="metric-cell">Ср.</th>
                <th class="metric-cell">P95</th>
                <th class="metric-cell">Размер</th>
                <th class="date-cell">Дата</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="run in recentRuns" :key="run.id">
                <td>
                  <v-chip size="x-small" color="primary" variant="tonal" class="text-uppercase">
                    {{ run.engineId }}
                  </v-chip>
                </td>
                <td>
                  <v-chip size="x-small" :color="runStatusColor(run.status)" variant="tonal">
                    {{ runStatusLabel(run.status) }}
                  </v-chip>
                </td>
                <template v-if="hasRunMetrics(run)">
                  <td class="metric-cell">{{ formatRunMs(run.avgMs) }}</td>
                  <td class="metric-cell">{{ formatRunMs(run.p95Ms) }}</td>
                  <td class="metric-cell">{{ formatRunBytes(run.outputBytes) }}</td>
                </template>
                <template v-else>
                  <td colspan="3" class="text-medium-emphasis">Метрики пока не записаны</td>
                </template>
                <td class="date-cell">{{ formatRunDate(run.createdAt) }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-card>
      </v-col>

      <v-col cols="12" lg="4">
        <v-card variant="outlined" class="side-card">
          <div class="section-header compact">
            <div>
              <div class="text-subtitle-1 font-weight-medium">Текущий статус</div>
              <div class="text-body-2 text-medium-emphasis">Последняя активность</div>
            </div>
          </div>

          <div v-if="latestRun" class="latest-run">
            <div class="latest-row">
              <span class="text-medium-emphasis">Последний запуск</span>
              <v-chip size="x-small" :color="runStatusColor(latestRun.status)" variant="tonal">
                {{ runStatusLabel(latestRun.status) }}
              </v-chip>
            </div>
            <div class="latest-row">
              <span class="text-medium-emphasis">Движок</span>
              <span class="text-uppercase">{{ latestRun.engineId }}</span>
            </div>
            <div class="latest-row">
              <span class="text-medium-emphasis">Итерации</span>
              <span>{{ latestRun.iterations }}</span>
            </div>
            <div class="latest-row">
              <span class="text-medium-emphasis">Дата</span>
              <span>{{ formatRunDate(latestRun.createdAt) }}</span>
            </div>
          </div>

          <div v-else class="side-empty text-body-2 text-medium-emphasis">
            После первого сохранённого запуска здесь появится быстрый срез состояния.
          </div>
        </v-card>
      </v-col>
    </v-row>
    <template #footer>
      <div>Управляйте шаблонами, benchmark-запусками и быстрым переходом в sandbox из одного рабочего пространства.</div>
    </template>
  </PageShell>
</template>

<style scoped>
.dashboard-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.summary-row {
  margin-bottom: 12px;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 8px;
  margin-bottom: 12px;
}

.quick-action {
  min-height: 72px;
  justify-content: flex-start;
  padding: 10px 12px;
}

.quick-action :deep(.v-btn__content) {
  min-width: 0;
  display: flex;
  align-items: flex-start;
  flex-direction: column;
  gap: 3px;
  text-align: left;
  white-space: normal;
}

.quick-action-title {
  font-weight: 700;
  line-height: 1.2;
}

.quick-action-subtitle {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 0.78rem;
  line-height: 1.25;
}

.summary-card {
  height: 100%;
}

.summary-card-body {
  min-height: 118px;
  display: flex;
  flex-direction: column;
}

.summary-label,
.summary-meta {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 0.82rem;
}

.summary-value {
  margin-top: 8px;
  font-size: 2rem;
  line-height: 1;
  font-weight: 700;
}

.summary-link {
  align-self: flex-start;
  margin-top: auto;
  margin-left: -8px;
}

.runs-card,
.side-card {
  height: 100%;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px 10px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.section-header.compact {
  border-bottom: none;
  padding-bottom: 6px;
}

.empty-state {
  padding: 36px 16px;
  text-align: center;
}

.runs-table {
  font-size: 0.88rem;
}

.metric-cell {
  text-align: right;
  white-space: nowrap;
}

.date-cell {
  white-space: nowrap;
}

.latest-run {
  padding: 4px 16px 16px;
}

.latest-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.1);
  font-size: 0.9rem;
}

.latest-row:last-child {
  border-bottom: none;
}

.side-empty {
  padding: 8px 16px 18px;
}

@media (max-width: 700px) {
  .quick-actions {
    grid-template-columns: 1fr;
  }

  .dashboard-header,
  .section-header {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
