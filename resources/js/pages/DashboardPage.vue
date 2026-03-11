<template>
  <div class="space-y-4">
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>

      <!-- ============================================ -->
      <!-- A. ОБЗОР — фильтр, подсказки, метрики, рост  -->
      <!-- ============================================ -->

      <!-- Фильтр периода + PDF -->
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
          <div class="flex flex-wrap items-center gap-1 bg-gray-100 rounded-lg p-0.5">
            <button v-for="p in relativePeriods" :key="p.value"
              @click="changePeriod(p.value)"
              :class="[
                'px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors whitespace-nowrap',
                period === p.value ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'
              ]">
              {{ p.label }}
            </button>
          </div>
          <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5">
            <button v-for="y in yearOptions" :key="y"
              @click="changePeriod(String(y))"
              :class="[
                'px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors',
                period === String(y) ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'
              ]">
              {{ y }}
            </button>
          </div>
        </div>
      </div>

      <!-- Подсказки -->
      <div v-if="hints.length" class="space-y-2">
        <div v-for="(hint, i) in hints" :key="i"
          :class="[
            'flex items-start gap-3 px-4 py-3 rounded-xl border text-sm',
            hint.type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' :
            hint.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' :
            hint.type === 'tip' ? 'bg-purple-50 border-purple-200 text-purple-800' :
            'bg-blue-50 border-blue-200 text-blue-800',
          ]">
          <div :class="[
            'w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5',
            hint.type === 'warning' ? 'bg-amber-100' :
            hint.type === 'success' ? 'bg-green-100' :
            hint.type === 'tip' ? 'bg-purple-100' : 'bg-blue-100',
          ]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path v-if="hint.type === 'warning'" stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
              <path v-else-if="hint.type === 'success'" stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M12 14a2 2 0 00.914-3.782 1.136 1.136 0 01-.535-.612.647.647 0 00-.379-.38.647.647 0 00-.379.38 1.136 1.136 0 01-.535.612A2 2 0 0012 14z"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-xs uppercase tracking-wide opacity-70">{{ t(`crm.dashboard.hints.${hint.key}_title`, hint.params || {}) }}</p>
            <p class="mt-0.5">{{ t(`crm.dashboard.hints.${hint.key}_msg`, hint.params || {}) }}</p>
          </div>
          <router-link v-if="hint.action" :to="hint.action"
            class="shrink-0 text-xs font-medium underline opacity-70 hover:opacity-100 mt-1">
            {{ t('crm.dashboard.goTo') }}
          </router-link>
          <button @click="hints.splice(i, 1)" class="shrink-0 opacity-40 hover:opacity-80 mt-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- Ключевые метрики -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <component :is="m.to ? 'router-link' : 'div'" v-for="m in metricCards" :key="m.key"
          :to="m.to || undefined"
          :class="[
            'bg-white rounded-xl border border-gray-200 px-4 py-3 transition-all group relative',
            m.to ? 'hover:border-blue-300 hover:shadow-md cursor-pointer' : ''
          ]">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ m.label }}</p>
          <div class="flex items-end gap-2">
            <p class="text-2xl font-bold mt-0.5" :class="m.color">{{ m.value }}</p>
            <span v-if="m.growth !== undefined && m.growth !== 0"
              class="text-xs font-semibold mb-1 px-1.5 py-0.5 rounded-full"
              :class="m.growth > 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'">
              {{ m.growth > 0 ? '+' : '' }}{{ m.growth }}%
            </span>
          </div>
          <p v-if="m.sub" class="text-xs text-gray-400 mt-0.5">{{ m.sub }}</p>
          <div v-if="m.tooltip" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-48 text-center z-10">
            {{ m.tooltip }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
          </div>
        </component>
      </div>

      <!-- Сравнение с предыдущим периодом -->
      <div v-if="hasGrowthData" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div v-for="item in comparisonItems" :key="item.key" class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ item.label }}</p>
          <p class="text-xl font-bold mt-1" :class="item.growth > 0 ? 'text-green-600' : item.growth < 0 ? 'text-red-600' : 'text-gray-400'">
            {{ item.growth > 0 ? '+' : '' }}{{ item.growth }}%
          </p>
          <p class="text-xs text-gray-400">{{ t('crm.dashboard.vsPrevPeriod') }}</p>
        </div>
      </div>

      <!-- Блок задач -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <router-link :to="{ path: '/app/tasks', query: { status: 'active' } }"
          class="bg-white rounded-xl border border-gray-200 px-4 py-3 hover:border-blue-300 hover:shadow-md cursor-pointer transition-all">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.tasksActive') }}</p>
          <p class="text-2xl font-bold mt-0.5 text-blue-600">{{ taskCounters.active }}</p>
        </router-link>
        <router-link :to="{ path: '/app/tasks', query: { due: 'today' } }"
          class="bg-white rounded-xl border border-gray-200 px-4 py-3 hover:border-amber-300 hover:shadow-md cursor-pointer transition-all">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.tasksToday') }}</p>
          <p class="text-2xl font-bold mt-0.5 text-amber-600">{{ taskCounters.today }}</p>
        </router-link>
        <router-link :to="{ path: '/app/tasks', query: { due: 'overdue' } }"
          class="bg-white rounded-xl border border-gray-200 px-4 py-3 hover:border-red-300 hover:shadow-md cursor-pointer transition-all">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.tasksOverdue') }}</p>
          <p class="text-2xl font-bold mt-0.5 text-red-600">{{ taskCounters.overdue }}</p>
        </router-link>
        <router-link :to="{ path: '/app/tasks', query: { status: 'pending_review' } }"
          class="bg-white rounded-xl border border-gray-200 px-4 py-3 hover:border-purple-300 hover:shadow-md cursor-pointer transition-all">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.tasksPendingReview') }}</p>
          <p class="text-2xl font-bold mt-0.5 text-purple-600">{{ taskCounters.pending_review }}</p>
        </router-link>
      </div>

      <!-- ============================================ -->
      <!-- B. ВОРОНКА И ЭТАПЫ                           -->
      <!-- ============================================ -->

      <!-- Воронка + Этапы side-by-side -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Воронка продаж -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.dashboard.funnelTitle') }}</h3>
            <span class="group relative cursor-help">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827m0 4h.01"/>
              </svg>
              <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-64 text-center z-10">
                {{ t('crm.dashboard.funnelTooltip') }}
                <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
              </div>
            </span>
          </div>
          <div v-if="funnelData.length" class="space-y-2">
            <div v-for="(step, i) in funnelData" :key="step.key">
              <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 w-24 shrink-0 truncate">{{ step.label }}</span>
                <div class="flex-1 relative">
                  <div class="h-7 rounded-md transition-all duration-700 flex items-center px-2.5"
                    :style="{ width: step.percent + '%', minWidth: '32px', background: stageColor(step.key) + '20', borderLeft: `3px solid ${stageColor(step.key)}` }">
                    <span class="text-xs font-bold" :style="{ color: stageColor(step.key) }">{{ step.count }}</span>
                  </div>
                </div>
                <span class="text-xs text-gray-400 w-10 text-right shrink-0">{{ step.percent }}%</span>
                <span v-if="i > 0 && step.dropoff != null" class="text-xs w-12 text-right shrink-0"
                  :class="step.dropoff > 50 ? 'text-red-500 font-semibold' : step.dropoff > 30 ? 'text-amber-500' : 'text-gray-400'">
                  -{{ step.dropoff }}%
                </span>
                <span v-else class="w-12 shrink-0"></span>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-gray-400 text-center py-8">{{ t('crm.dashboard.noData') }}</div>
        </div>

        <!-- Заявки по этапам -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.dashboard.stagesTitle') }}</h3>
            <span class="group relative cursor-help">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827m0 4h.01"/>
              </svg>
              <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-64 text-center z-10">
                {{ t('crm.dashboard.stagesTooltip') }}
                <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
              </div>
            </span>
          </div>
          <div class="space-y-2.5">
            <router-link v-for="stage in stageRows" :key="stage.key"
              :to="{ name: 'cases', query: { stage: stage.key } }"
              class="flex items-center gap-3 group hover:bg-gray-50 rounded-lg px-1 -mx-1 py-0.5 transition-colors">
              <span class="text-xs text-gray-500 w-28 shrink-0 group-hover:text-blue-600 transition-colors">{{ stage.label }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700" :style="{ width: stage.percent + '%', background: stageColor(stage.key) }"/>
              </div>
              <span class="text-xs font-bold text-gray-700 w-6 text-right">{{ stage.count }}</span>
            </router-link>
          </div>
        </div>
      </div>

      <!-- SLA по этапам -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-2 mb-4">
          <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.dashboard.stageAnalytics') }}</h3>
          <span class="group relative cursor-help">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827m0 4h.01"/>
            </svg>
            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-64 text-center z-10">
              {{ t('crm.dashboard.stageAnalyticsTooltip') }}
              <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
            </div>
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-gray-500 text-xs uppercase tracking-wide">
              <tr>
                <th class="text-left px-4 py-2.5 font-medium">{{ t('crm.dashboard.stageCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.slaNorm') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.avgTime') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.deviation') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.slaCompliance') }}</th>
                <th class="px-4 py-2.5 w-28"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="s in stageAnalyticsRows" :key="s.stage" class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-2.5 font-medium text-gray-800">{{ stageLabel(s.stage) }}</td>
                <td class="px-4 py-2.5 text-right text-gray-500">{{ s.sla_norm_hours != null ? formatHours(s.sla_norm_hours) : '--' }}</td>
                <td class="px-4 py-2.5 text-right text-gray-700">{{ s.avg_hours != null ? formatHours(s.avg_hours) : '--' }}</td>
                <td class="px-4 py-2.5 text-right font-semibold" :class="deviationColor(s.deviation)">
                  {{ s.deviation != null ? (s.deviation > 0 ? '+' : '') + formatHours(Math.abs(s.deviation)) : '--' }}
                </td>
                <td class="px-4 py-2.5 text-right font-bold" :class="slaColor(s.sla_compliance)">
                  {{ s.total_transitions > 0 ? s.sla_compliance + '%' : '--' }}
                </td>
                <td class="px-4 py-2.5">
                  <div v-if="s.total_transitions > 0" class="bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                      :class="s.sla_compliance >= 80 ? 'bg-green-500' : s.sla_compliance >= 60 ? 'bg-amber-400' : 'bg-red-500'"
                      :style="{ width: s.sla_compliance + '%' }"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- C. ТРЕНДЫ И ИСТОЧНИКИ                        -->
      <!-- ============================================ -->

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Динамика -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 lg:col-span-2">
          <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.chartTitle') }}</h3>
          <div class="h-48 relative">
            <svg v-if="chartData.length" class="w-full h-full" :viewBox="`0 0 ${chartW} ${chartH}`" preserveAspectRatio="none">
              <line v-for="i in 4" :key="'g'+i" :x1="0" :y1="chartH * i / 4" :x2="chartW" :y2="chartH * i / 4" stroke="#f3f4f6" stroke-width="1"/>
              <polygon :points="areaCreated" fill="rgba(59,130,246,0.1)"/>
              <polyline :points="lineCreated" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linejoin="round"/>
              <polygon :points="areaCompleted" fill="rgba(16,185,129,0.1)"/>
              <polyline :points="lineCompleted" fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
            </svg>
            <div v-else class="flex items-center justify-center h-full text-sm text-gray-400">{{ t('crm.dashboard.noData') }}</div>
          </div>
          <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-blue-500 rounded"></span> {{ t('crm.dashboard.created') }}</span>
            <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-green-500 rounded"></span> {{ t('crm.dashboard.completed') }}</span>
          </div>
        </div>

        <!-- Источники лидов -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.leadSourcesTitle') }}</h3>
          <div v-if="leadSources.length" class="flex flex-col items-center">
            <svg width="140" height="140" viewBox="0 0 140 140">
              <circle v-for="(s, i) in pieSlices" :key="i"
                cx="70" cy="70" r="55" fill="none" :stroke="s.color" stroke-width="30"
                :stroke-dasharray="`${s.dash} ${s.gap}`" :stroke-dashoffset="s.offset"
                class="transition-all duration-500"
              />
            </svg>
            <div class="mt-3 w-full space-y-1.5">
              <div v-for="(s, i) in leadSources" :key="i" class="flex items-center gap-2 text-xs">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{background: sourceColors[i % sourceColors.length]}"></span>
                <span class="flex-1 text-gray-600 truncate">{{ sourceLabels[s.source] || s.source }}</span>
                <span class="text-gray-400 text-xs">{{ totalLeads > 0 ? Math.round(s.count / totalLeads * 100) : 0 }}%</span>
                <span class="font-bold text-gray-800">{{ s.count }}</span>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-gray-400 text-center py-8">{{ t('crm.dashboard.noData') }}</div>
          <router-link :to="{ name: 'leadgen' }"
            class="mt-4 flex items-center justify-center gap-2 w-full py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-semibold rounded-lg transition-colors">
            {{ t('crm.dashboard.leadgenBtn') }}
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
          </router-link>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- D. КОМАНДА — менеджеры + рейтинг             -->
      <!-- ============================================ -->

      <div v-if="stats?.managers?.length" class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.managersLoad') }}</h3>

        <!-- Рейтинг топ-3 (если больше 1 менеджера) -->
        <div v-if="managerRanking.length > 1" class="grid grid-cols-3 gap-3 mb-5">
          <div v-for="(rank, i) in managerRanking" :key="rank.id"
            class="p-3 rounded-lg border"
            :class="i === 0 ? 'border-yellow-300 bg-yellow-50' : i === 1 ? 'border-gray-300 bg-gray-50' : 'border-orange-200 bg-orange-50'">
            <div class="flex items-center gap-2.5 mb-2">
              <div class="relative flex-shrink-0">
                <img v-if="rank.avatar_url" :src="rank.avatar_url" :alt="rank.name"
                  class="w-10 h-10 rounded-full object-cover border-2"
                  :class="i === 0 ? 'border-yellow-400' : i === 1 ? 'border-gray-400' : 'border-orange-400'" />
                <div v-else class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white border-2"
                  :class="i === 0 ? 'border-yellow-400' : i === 1 ? 'border-gray-400' : 'border-orange-400'"
                  :style="{ backgroundColor: managerInitialsColor(rank.name) }">
                  {{ managerInitials(rank.name) }}
                </div>
                <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold text-white"
                  :class="i === 0 ? 'bg-yellow-500' : i === 1 ? 'bg-gray-500' : 'bg-orange-500'">{{ i + 1 }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <p class="font-semibold text-sm text-gray-800 truncate">{{ rank.name }}</p>
                <p class="text-xs text-gray-500">{{ rank.score }} {{ t('crm.dashboard.rankingPts') }} | {{ rank.conversion }}%</p>
              </div>
            </div>
            <p class="text-xs text-gray-600 italic leading-tight">{{ rank.explanation }}</p>
            <div v-if="rank.awards.length" class="flex items-center gap-1.5 mt-1.5">
              <span v-for="award in rank.awards" :key="award.key"
                class="inline-flex items-center gap-0.5 text-[9px] font-medium px-1.5 py-0.5 rounded-full"
                :class="award.cls">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path :d="award.icon" /></svg>
                {{ award.label }}
              </span>
            </div>
          </div>
        </div>

        <!-- Таблица менеджеров -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-gray-500 text-xs uppercase tracking-wide">
              <tr>
                <th class="text-left px-4 py-2.5 font-medium">{{ t('crm.dashboard.managerCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.activeCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.completedCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.overdueCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.avgTimeCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.conversionCol') }}</th>
                <th class="px-4 py-2.5 w-28"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="m in stats.managers" :key="m.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-2.5 font-medium text-gray-800">{{ m.name }}</td>
                <td class="px-4 py-2.5 text-right font-bold text-gray-900">{{ m.active_cases }}</td>
                <td class="px-4 py-2.5 text-right text-green-600 font-semibold">{{ m.completed_cases }}</td>
                <td class="px-4 py-2.5 text-right font-semibold" :class="m.overdue_cases > 0 ? 'text-red-600' : 'text-gray-400'">{{ m.overdue_cases }}</td>
                <td class="px-4 py-2.5 text-right text-gray-700">{{ m.avg_hours > 0 ? formatHours(m.avg_hours) : '--' }}</td>
                <td class="px-4 py-2.5 text-right font-semibold" :class="m.conversion >= 80 ? 'text-green-600' : m.conversion >= 50 ? 'text-amber-600' : 'text-gray-500'">{{ m.conversion }}%</td>
                <td class="px-4 py-2.5">
                  <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                      :class="m.active_cases > maxManagerLoad * 0.8 ? 'bg-red-500' : m.active_cases > maxManagerLoad * 0.5 ? 'bg-amber-400' : 'bg-indigo-500'"
                      :style="{ width: `${Math.min(100, (m.active_cases / maxManagerLoad) * 100)}%` }"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- E. ЭФФЕКТИВНОСТЬ — конверсии + время          -->
      <!-- ============================================ -->

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 group relative">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.conversionLeadCase') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-blue-600">{{ metrics.conversion_lead_case }}%</p>
          </div>
          <div class="mt-3 bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="h-full bg-blue-500 rounded-full transition-all" :style="{ width: metrics.conversion_lead_case + '%' }"/>
          </div>
          <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-64 text-center z-10">
            {{ t('crm.dashboard.conversionLeadCaseTip') }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 group relative">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.conversionCaseVisa') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-green-600">{{ metrics.conversion_case_visa }}%</p>
          </div>
          <div class="mt-3 bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: metrics.conversion_case_visa + '%' }"/>
          </div>
          <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-64 text-center z-10">
            {{ t('crm.dashboard.conversionCaseVisaTip') }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 group relative">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.avgProcessing') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-indigo-600">{{ formatHours(metrics.avg_processing_hours) }}</p>
          </div>
          <p class="text-xs text-gray-400 mt-1">{{ t('crm.dashboard.avgProcessingSub') }}</p>
          <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-64 text-center z-10">
            {{ t('crm.dashboard.avgProcessingTip') }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
          </div>
        </div>
        <router-link :to="{ name: 'clients' }" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 hover:shadow-md transition-all group relative">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.repeatClients') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-purple-600">{{ stats?.repeat_clients ?? 0 }}</p>
            <span v-if="stats?.clients_total" class="text-xs text-gray-400 mb-1">/ {{ stats.clients_total }}</span>
          </div>
          <p class="text-xs text-gray-400 mt-1">{{ t('crm.dashboard.repeatClientsSub') }}</p>
          <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-64 text-center z-10">
            {{ t('crm.dashboard.repeatClientsTip') }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
          </div>
        </router-link>
      </div>

      <!-- ============================================ -->
      <!-- F. ФИНАНСЫ                                    -->
      <!-- ============================================ -->

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.dashboard.financialTitle') }}</h3>
          <button v-if="!financialLoaded" @click="loadFinancial"
            class="text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 border transition-colors">
            {{ t('crm.dashboard.financialLoad') }}
          </button>
        </div>
        <div v-if="financialLoading" class="flex justify-center py-6">
          <div class="animate-spin w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
        </div>
        <div v-else-if="financialData" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <div v-for="f in financialCards" :key="f.key" class="text-center p-3 rounded-lg bg-gray-50 border border-gray-100">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ f.label }}</p>
            <p class="text-lg font-bold mt-1" :class="f.color">{{ f.value }}</p>
          </div>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- G. РЫНОК — популярные страны                  -->
      <!-- ============================================ -->

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-800 text-sm mb-1">{{ t('crm.dashboard.popularCountriesTitle') }}</h3>
        <p class="text-xs text-gray-400 mb-3">{{ t('crm.dashboard.popularCountriesSub') }}</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Топ 5 -->
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">{{ t('crm.dashboard.topCountries') }}</p>
            <div v-if="topPopularFiltered.length" class="space-y-2">
              <router-link v-for="(c, i) in topPopularFiltered" :key="c.country_code"
                :to="{ name: 'countries.show', params: { code: c.country_code } }"
                class="flex items-center gap-2 text-xs group hover:bg-gray-50 rounded-lg px-1 -mx-1 py-1 transition-colors">
                <span class="font-bold text-gray-400 w-4">{{ i + 1 }}</span>
                <span class="text-sm">{{ c.flag_emoji }}</span>
                <span class="flex-1 text-gray-700 group-hover:text-blue-600 transition-colors truncate">{{ c.name }}</span>
                <span v-if="c.agency_works" class="text-xs font-medium px-1.5 py-0.5 bg-green-50 text-green-600 rounded-full">{{ t('crm.dashboard.works') }}</span>
                <span v-else class="text-xs font-medium px-1.5 py-0.5 bg-red-50 text-red-500 rounded-full">{{ t('crm.dashboard.notWorks') }}</span>
              </router-link>
            </div>
            <p v-else class="text-xs text-gray-400">{{ t('crm.dashboard.noData') }}</p>
          </div>

          <!-- Высокий интерес -->
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">{{ t('crm.dashboard.highInterest') }}</p>
            <div v-if="trendingPopularFiltered.length" class="space-y-1.5">
              <router-link v-for="(c, i) in trendingPopularFiltered" :key="c.country_code"
                :to="{ name: 'countries.show', params: { code: c.country_code } }"
                class="flex items-center gap-2 text-xs group hover:bg-gray-50 rounded-lg px-1 -mx-1 py-0.5 transition-colors">
                <span class="font-bold text-gray-400 w-4">{{ i + 1 }}</span>
                <span class="text-sm">{{ c.flag_emoji }}</span>
                <span class="flex-1 text-gray-600 group-hover:text-blue-600 transition-colors truncate">{{ c.name }}</span>
                <span v-if="c.agency_works" class="text-[9px] font-medium px-1.5 py-0.5 bg-green-50 text-green-600 rounded-full">{{ t('crm.dashboard.works') }}</span>
                <span v-else class="text-[9px] font-medium px-1.5 py-0.5 bg-red-50 text-red-500 rounded-full">{{ t('crm.dashboard.notWorks') }}</span>
              </router-link>
            </div>
            <p v-else class="text-xs text-gray-400">{{ t('crm.dashboard.noData') }}</p>
          </div>

          <!-- Новые возможности -->
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">{{ t('crm.dashboard.newOpportunities') }}</p>
            <div v-if="newOpportunities.length" class="space-y-1.5">
              <router-link v-for="(c, i) in newOpportunities" :key="c.country_code"
                :to="{ name: 'countries.show', params: { code: c.country_code } }"
                class="flex items-center gap-2 text-xs group hover:bg-gray-50 rounded-lg px-1 -mx-1 py-0.5 transition-colors">
                <span class="font-bold text-gray-400 w-4">{{ i + 1 }}</span>
                <span class="text-sm">{{ c.flag_emoji }}</span>
                <span class="flex-1 text-gray-600 group-hover:text-blue-600 transition-colors truncate">{{ c.name }}</span>
                <span class="text-[9px] font-medium px-1.5 py-0.5 bg-purple-50 text-purple-600 rounded-full">{{ t('crm.dashboard.opportunity') }}</span>
              </router-link>
            </div>
            <p v-else class="text-xs text-gray-400">{{ t('crm.dashboard.noData') }}</p>
          </div>
        </div>

        <div v-if="!topPopularFiltered.length && !trendingPopularFiltered.length && !newOpportunities.length" class="text-sm text-gray-400 text-center py-8">{{ t('crm.dashboard.noData') }}</div>
      </div>

      <!-- ============================================ -->
      <!-- H. ПЛАНИРОВАНИЕ — цели + прогноз              -->
      <!-- ============================================ -->

      <!-- Цели и план -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.dashboard.goalsTitle') }}</h3>
          <button @click="showGoalForm = !showGoalForm"
            class="text-xs font-medium px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors">
            {{ showGoalForm ? t('crm.dashboard.goalsHide') : t('crm.dashboard.goalsSet') }}
          </button>
        </div>
        <div v-if="showGoalForm" class="mb-4 p-4 bg-gray-50 rounded-lg border space-y-3">
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase">{{ t('crm.dashboard.goalClients') }}</label>
              <input v-model.number="goalForm.target_clients" type="number" min="0"
                class="mt-1 w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-300 outline-none">
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase">{{ t('crm.dashboard.goalCases') }}</label>
              <input v-model.number="goalForm.target_cases" type="number" min="0"
                class="mt-1 w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-300 outline-none">
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase">{{ t('crm.dashboard.goalRevenue') }}</label>
              <input v-model.number="goalForm.target_revenue" type="number" min="0"
                class="mt-1 w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-300 outline-none">
            </div>
          </div>
          <button @click="saveGoals" :disabled="savingGoal"
            class="px-4 py-2 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ savingGoal ? '...' : t('crm.dashboard.goalsSave') }}
          </button>
        </div>
        <div v-if="goalsData" class="space-y-3">
          <div v-for="g in goalProgressItems" :key="g.key">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs text-gray-600">{{ g.label }}</span>
              <span class="text-xs font-semibold" :class="g.pct >= 100 ? 'text-green-600' : g.pct >= 50 ? 'text-blue-600' : 'text-gray-500'">
                {{ g.current }} / {{ g.target }} ({{ g.pct }}%)
              </span>
            </div>
            <div class="bg-gray-100 rounded-full h-2.5 overflow-hidden">
              <div class="h-full rounded-full transition-all duration-700"
                :class="g.pct >= 100 ? 'bg-green-500' : g.pct >= 50 ? 'bg-blue-500' : 'bg-gray-400'"
                :style="{ width: Math.min(100, g.pct) + '%' }"/>
            </div>
          </div>
          <p v-if="!goalProgressItems.length" class="text-xs text-gray-400 text-center py-4">{{ t('crm.dashboard.goalsEmpty') }}</p>
        </div>
      </div>

      <!-- Калькулятор прогноза роста -->
      <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl border border-indigo-200 p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="font-semibold text-indigo-900 text-sm">{{ t('crm.forecast.title') }}</h3>
            <p class="text-xs text-indigo-600 mt-0.5">{{ t('crm.forecast.subtitle') }}</p>
          </div>
          <button @click="showForecast = !showForecast"
            class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
            :class="showForecast ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600 border border-indigo-300 hover:bg-indigo-100'">
            {{ showForecast ? t('crm.forecast.hide') : t('crm.forecast.show') }}
          </button>
        </div>

        <div v-if="showForecast" class="space-y-4">
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div>
              <label class="text-xs font-medium text-indigo-700 uppercase tracking-wide">{{ t('crm.forecast.clientsPerMonth') }}</label>
              <input v-model.number="forecast.clientsPerMonth" type="number" min="1" max="10000"
                class="mt-1 w-full px-3 py-2 text-sm border border-indigo-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
              <label class="text-xs font-medium text-indigo-700 uppercase tracking-wide">{{ t('crm.forecast.avgCheck') }}</label>
              <input v-model.number="forecast.avgCheck" type="number" min="10" max="100000"
                class="mt-1 w-full px-3 py-2 text-sm border border-indigo-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
              <label class="text-xs font-medium text-indigo-700 uppercase tracking-wide">{{ t('crm.forecast.managers') }}</label>
              <input v-model.number="forecast.managers" type="number" min="1" max="100"
                class="mt-1 w-full px-3 py-2 text-sm border border-indigo-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
              <label class="text-xs font-medium text-indigo-700 uppercase tracking-wide">{{ t('crm.forecast.growthPct') }}</label>
              <input v-model.number="forecast.growthPct" type="number" min="0" max="300"
                class="mt-1 w-full px-3 py-2 text-sm border border-indigo-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
          </div>

          <div class="bg-white/60 rounded-lg p-4 border border-indigo-100">
            <p class="text-xs font-medium text-indigo-700 uppercase tracking-wide mb-3">{{ t('crm.forecast.managerCapacity') }}</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
              <div>
                <p class="text-lg font-bold text-gray-900">{{ stats?.managers_count ?? 0 }}</p>
                <p class="text-xs text-gray-500">{{ t('crm.forecast.currentManagers') }}</p>
              </div>
              <div>
                <p class="text-lg font-bold" :class="realAvgHours > 0 ? 'text-gray-900' : 'text-gray-400'">{{ realAvgHours > 0 ? formatHours(realAvgHours) : '--' }}</p>
                <p class="text-xs text-gray-500">{{ t('crm.forecast.realAvgTime') }}</p>
              </div>
              <div>
                <p class="text-lg font-bold text-blue-600">{{ managerCapacity.withoutCrm }}</p>
                <p class="text-xs text-gray-500">{{ t('crm.forecast.capacityWithout') }}</p>
              </div>
              <div>
                <p class="text-lg font-bold text-green-600">{{ managerCapacity.withCrm }}</p>
                <p class="text-xs text-gray-500">{{ t('crm.forecast.capacityWith') }}</p>
              </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3">
              <div class="bg-gray-50 rounded-lg p-2.5 text-center">
                <p class="text-xs text-gray-500">{{ t('crm.forecast.totalCapacityWithout') }}</p>
                <p class="text-sm font-bold text-gray-700">{{ teamCapacity.without }} {{ t('crm.forecast.clientsMonth') }}</p>
              </div>
              <div class="bg-green-50 rounded-lg p-2.5 text-center">
                <p class="text-xs text-green-600">{{ t('crm.forecast.totalCapacityWith') }}</p>
                <p class="text-sm font-bold text-green-700">{{ teamCapacity.with }} {{ t('crm.forecast.clientsMonth') }}</p>
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-white/60 border-b text-indigo-700 text-xs uppercase tracking-wide">
                <tr>
                  <th class="text-left px-3 py-2 font-medium">{{ t('crm.forecast.yearCol') }}</th>
                  <th class="text-right px-3 py-2 font-medium">{{ t('crm.forecast.clientsCol') }}</th>
                  <th class="text-right px-3 py-2 font-medium">{{ t('crm.forecast.managersWithout') }}</th>
                  <th class="text-right px-3 py-2 font-medium">{{ t('crm.forecast.managersWith') }}</th>
                  <th class="text-right px-3 py-2 font-medium">{{ t('crm.forecast.savings') }}</th>
                  <th class="text-right px-3 py-2 font-medium">{{ t('crm.forecast.revenueCol') }}</th>
                  <th class="px-3 py-2 w-24"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-indigo-100">
                <tr v-for="row in forecastRows" :key="row.year" class="hover:bg-white/40 transition-colors">
                  <td class="px-3 py-2.5 font-bold text-indigo-900">{{ row.year }}</td>
                  <td class="px-3 py-2.5 text-right font-semibold text-gray-800">{{ row.clients.toLocaleString() }}</td>
                  <td class="px-3 py-2.5 text-right text-gray-500">{{ row.managersWithout }}</td>
                  <td class="px-3 py-2.5 text-right font-semibold text-green-700">{{ row.managersWith }}</td>
                  <td class="px-3 py-2.5 text-right text-green-600 font-semibold">
                    <span v-if="row.saved > 0">-{{ row.saved }} {{ t('crm.forecast.people') }}</span>
                    <span v-else class="text-gray-400">--</span>
                  </td>
                  <td class="px-3 py-2.5 text-right font-bold text-green-700">${{ row.revenue.toLocaleString() }}</td>
                  <td class="px-3 py-2.5">
                    <div class="bg-indigo-100 rounded-full h-2 overflow-hidden">
                      <div class="h-full bg-indigo-500 rounded-full transition-all" :style="{ width: Math.min(100, row.clients / maxForecastClients * 100) + '%' }"/>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="forecastRows.length" class="bg-white/60 rounded-lg p-4 border border-indigo-100">
            <div class="text-center">
              <p class="text-sm font-semibold text-indigo-900">
                {{ t('crm.forecast.motivation', {
                  year: forecastRows[forecastRows.length - 1].year,
                  revenue: forecastRows[forecastRows.length - 1].revenue.toLocaleString(),
                  clients: forecastRows[forecastRows.length - 1].clients.toLocaleString()
                }) }}
              </p>
            </div>
            <div v-if="totalSaved > 0" class="mt-3 bg-green-50 rounded-lg p-3 text-center border border-green-200">
              <p class="text-xs font-semibold text-green-800">
                {{ t('crm.forecast.automationSaving', { saved: totalSaved, salaryTotal: (totalSaved * 500).toLocaleString() }) }}
              </p>
              <p class="text-xs text-green-600 mt-1">{{ t('crm.forecast.automationSavingSub') }}</p>
            </div>
            <p class="text-xs text-indigo-600 mt-3 text-center">{{ t('crm.forecast.motivationSub') }}</p>
          </div>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- I. ЛЕНТА АКТИВНОСТИ                          -->
      <!-- ============================================ -->

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.dashboard.activityTitle') }}</h3>
          <button v-if="!activityLoaded" @click="loadActivity"
            class="text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 border transition-colors">
            {{ t('crm.dashboard.activityLoad') }}
          </button>
        </div>
        <div v-if="activityLoading" class="flex justify-center py-6">
          <div class="animate-spin w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
        </div>
        <div v-else-if="activityFeed.length" class="space-y-1 max-h-64 overflow-y-auto">
          <router-link v-for="(ev, i) in activityFeed" :key="i"
            :to="ev.case_id ? { name: 'cases.show', params: { id: ev.case_id } } : ''"
            :class="[
              'flex items-start gap-3 text-xs py-2 px-2 rounded-lg border-b border-gray-50 last:border-0 transition-colors',
              ev.case_id ? 'hover:bg-gray-50 cursor-pointer' : ''
            ]">
            <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 mt-0.5"
              :class="activityDotClass(ev.type)">
              <svg v-if="ev.type === 'stage_change'" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
              </svg>
              <svg v-else-if="ev.type === 'case_created'" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
              </svg>
              <svg v-else-if="ev.type === 'document_uploaded'" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
              </svg>
              <svg v-else-if="ev.type === 'payment'" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <svg v-else-if="ev.type === 'comment'" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
              </svg>
              <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-gray-700">
                <template v-if="ev.type === 'stage_change' && ev.old_value && ev.new_value">
                  {{ t('crm.dashboard.activityStageChange', { old: stageLabel(ev.old_value), new: stageLabel(ev.new_value) }) }}
                </template>
                <template v-else>{{ ev.description }}</template>
              </p>
              <p class="text-xs text-gray-400 mt-0.5">
                <span v-if="ev.user_name">{{ ev.user_name }} -- </span>
                <span v-if="ev.case_number">{{ ev.case_number }} -- </span>
                {{ formatDate(ev.created_at) }}
              </p>
            </div>
          </router-link>
        </div>
        <p v-else-if="activityLoaded" class="text-xs text-gray-400 text-center py-6">{{ t('crm.dashboard.noData') }}</p>
      </div>

    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { dashboardApi } from '@/api/dashboard';
import { tasksApi } from '@/api/tasks';

const { t } = useI18n();

const loading = ref(true);
const stats = ref(null);
const hints = ref([]);
const period = ref('30d');
const taskCounters = ref({ active: 0, today: 0, overdue: 0, pending_review: 0 });
const showForecast = ref(false);
const forecast = ref({
  clientsPerMonth: 20,
  avgCheck: 150,
  managers: 3,
  growthPct: 30,
});

// Цели
const showGoalForm = ref(false);
const savingGoal = ref(false);
const goalsData = ref(null);
const goalForm = ref({ target_clients: 0, target_cases: 0, target_revenue: 0 });

// Активность
const activityFeed = ref([]);
const activityLoading = ref(false);
const activityLoaded = ref(false);

// Финансы
const financialData = ref(null);
const financialLoading = ref(false);
const financialLoaded = ref(false);

const currentYear = new Date().getFullYear();

const relativePeriods = computed(() => [
  { value: 'all',  label: t('crm.dashboard.periodAll') },
  { value: '1d',   label: t('crm.dashboard.period1d') },
  { value: '3d',   label: t('crm.dashboard.period3d') },
  { value: '7d',   label: t('crm.dashboard.period7d') },
  { value: '30d',  label: t('crm.dashboard.period30d') },
  { value: '60d',  label: t('crm.dashboard.period60d') },
  { value: '90d',  label: t('crm.dashboard.period90d') },
  { value: '365d', label: t('crm.dashboard.period365d') },
]);

const yearOptions = computed(() => {
  const years = [];
  for (let y = currentYear; y <= currentYear + 2; y++) {
    years.push(y);
  }
  return years;
});

const STAGES = computed(() => [
  { key: 'lead',          label: t('crm.stages.lead') },
  { key: 'qualification', label: t('crm.stages.qualification') },
  { key: 'documents',     label: t('crm.stages.documents') },
  { key: 'doc_review',    label: t('crm.stages.doc_review') },
  { key: 'translation',   label: t('crm.stages.translation') },
  { key: 'ready',         label: t('crm.stages.ready') },
  { key: 'review',        label: t('crm.stages.review') },
  { key: 'result',        label: t('crm.stages.result') },
]);

const STAGE_COLORS = {
  lead: '#3b82f6', qualification: '#8b5cf6', documents: '#f59e0b',
  doc_review: '#06b6d4', translation: '#ec4899', ready: '#f97316',
  review: '#6366f1', result: '#10b981',
};

function stageColor(key) { return STAGE_COLORS[key] || '#6b7280'; }
function stageLabel(key) {
  const s = STAGES.value.find(s => s.key === key);
  return s ? s.label : key;
}

function formatHours(h) {
  if (h == null || h === 0) return '0';
  h = Math.abs(h);
  if (h < 1) return `${Math.round(h * 60)} ${t('crm.dashboard.minuteShort')}`;
  if (h < 24) return `${Math.round(h)} ${t('crm.dashboard.hourShort')}`;
  const days = Math.round(h / 24);
  return `${days} ${t('crm.dashboard.dayShort')}`;
}

function deviationColor(d) {
  if (d == null) return 'text-gray-400';
  if (d <= 0) return 'text-green-600';
  return 'text-red-600';
}

function slaColor(pct) {
  if (pct >= 80) return 'text-green-600';
  if (pct >= 60) return 'text-amber-600';
  return 'text-red-600';
}

const sourceColors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#ef4444', '#f97316', '#84cc16', '#14b8a6'];
const sourceLabels = computed(() => ({
  direct:       t('crm.sources.direct'),
  referral:     t('crm.sources.referral'),
  marketplace:  t('crm.sources.marketplace'),
  repeat:       t('crm.sources.repeat'),
  instagram:    'Instagram',
  facebook:     'Facebook',
  telegram:     'Telegram',
  whatsapp:     'WhatsApp',
  website:      t('crm.sources.website'),
  google:       'Google / SEO',
  google_ads:   'Google Ads',
  youtube:      'YouTube',
  tiktok:       'TikTok',
  partner:      t('crm.sources.partner'),
  offline:      t('crm.sources.offline'),
  exhibition:   t('crm.sources.exhibition'),
  email:        t('crm.sources.email'),
  cold:         t('crm.sources.cold'),
  other:        t('crm.sources.other'),
}));

const metrics = computed(() => stats.value?.metrics ?? {
  new_leads: 0, completed: 0, visa_issued: 0, completed_total: 0,
  conversion_lead_case: 0, conversion_case_visa: 0, avg_processing_hours: 0,
});

const growth = computed(() => stats.value?.growth ?? {});
const hasGrowthData = computed(() => {
  const g = growth.value;
  return g.new_leads || g.completed || g.visa_issued;
});

const metricCards = computed(() => {
  const m = metrics.value;
  const c = stats.value?.cases ?? {};
  const g = growth.value;
  return [
    {
      key: 'active', label: t('crm.dashboard.active'), value: c.total_active ?? 0,
      color: 'text-gray-900', to: { name: 'cases', query: { status: 'active' } },
      tooltip: t('crm.dashboard.activeTooltip'),
    },
    {
      key: 'new', label: t('crm.dashboard.newLeads'), value: m.new_leads ?? 0,
      color: 'text-blue-600', growth: g.new_leads,
      to: { name: 'cases', query: { stage: 'lead' } },
      tooltip: t('crm.dashboard.newLeadsTooltip'),
    },
    {
      key: 'completed', label: t('crm.dashboard.completedPeriod'), value: m.completed ?? 0,
      color: 'text-green-600', growth: g.completed,
      to: { name: 'cases', query: { stage: 'result' } },
      tooltip: t('crm.dashboard.completedTooltip'),
    },
    {
      key: 'overdue', label: t('crm.dashboard.overdue'), value: c.overdue ?? 0,
      color: c.overdue > 0 ? 'text-red-600' : 'text-gray-900',
      to: { name: 'cases', query: { status: 'overdue' } },
      tooltip: t('crm.dashboard.overdueTooltip'),
    },
    {
      key: 'critical', label: t('crm.dashboard.critical'), value: c.critical ?? 0,
      color: c.critical > 0 ? 'text-amber-600' : 'text-gray-900',
      to: { name: 'cases', query: { status: 'critical' } },
      tooltip: t('crm.dashboard.criticalTooltip'),
    },
    {
      key: 'unassigned', label: t('crm.dashboard.unassigned'), value: c.unassigned ?? 0,
      color: c.unassigned > 0 ? 'text-purple-600' : 'text-gray-900',
      to: { name: 'cases', query: { assigned_to: 'unassigned' } },
      tooltip: t('crm.dashboard.unassignedTooltip'),
    },
  ];
});

const stageRows = computed(() => {
  if (!stats.value?.cases?.by_stage) return [];
  const byStage = stats.value.cases.by_stage;
  const maxCount = Math.max(1, ...Object.values(byStage).map(Number));
  return STAGES.value.map(s => ({
    ...s,
    count: Number(byStage[s.key] ?? 0),
    percent: Math.round((Number(byStage[s.key] ?? 0) / maxCount) * 100),
  }));
});

const stageAnalyticsRows = computed(() => {
  if (!stats.value?.stage_analytics) return [];
  const sa = stats.value.stage_analytics;
  return STAGES.value.map(s => sa[s.key] ?? { stage: s.key, total_transitions: 0, avg_hours: null, max_hours: null, sla_norm_hours: null, sla_compliance: 100, deviation: null, overdue_count: 0 });
});

const maxManagerLoad = computed(() =>
  Math.max(1, ...(stats.value?.managers ?? []).map(m => m.active_cases))
);

const leadSources = computed(() => stats.value?.lead_sources ?? []);
const totalLeads = computed(() => leadSources.value.reduce((s, v) => s + v.count, 0));
const topPopular = computed(() => stats.value?.popular_countries?.top ?? []);
const trendingPopular = computed(() => stats.value?.popular_countries?.trending ?? []);

// Фильтрованные списки: без безвизовых, ограничение 5
const topPopularFiltered = computed(() =>
  topPopular.value.filter(c => c.visa_regime !== 'visa_free').slice(0, 5)
);
const trendingPopularFiltered = computed(() =>
  trendingPopular.value.filter(c => c.visa_regime !== 'visa_free').slice(0, 5)
);
// Новые возможности: страны с высоким спросом, где агентство НЕ работает
const newOpportunities = computed(() => {
  const all = [...topPopular.value, ...trendingPopular.value];
  const seen = new Set();
  return all
    .filter(c => c.visa_regime !== 'visa_free' && !c.agency_works)
    .filter(c => { if (seen.has(c.country_code)) return false; seen.add(c.country_code); return true; })
    .slice(0, 5);
});

// Pie chart slices
const pieSlices = computed(() => {
  const total = leadSources.value.reduce((s, v) => s + v.count, 0);
  if (total === 0) return [];
  const circ = 2 * Math.PI * 55;
  let offset = 0;
  return leadSources.value.map((s, i) => {
    const pct = s.count / total;
    const dash = circ * pct;
    const gap = circ - dash;
    const slice = { dash, gap, offset: -offset + circ / 4, color: sourceColors[i % sourceColors.length] };
    offset += dash;
    return slice;
  });
});

// Line chart
const chartData = computed(() => stats.value?.daily_trend ?? []);
const chartW = 600;
const chartH = 150;

function linePoints(key) {
  if (!chartData.value.length) return '';
  const maxVal = Math.max(1, ...chartData.value.map(d => Math.max(d.created, d.completed)));
  const step = chartW / Math.max(1, chartData.value.length - 1);
  return chartData.value.map((d, i) => `${i * step},${chartH - (d[key] / maxVal * (chartH - 10))}`).join(' ');
}

function areaPoints(key) {
  if (!chartData.value.length) return '';
  const pts = linePoints(key);
  const step = chartW / Math.max(1, chartData.value.length - 1);
  const last = (chartData.value.length - 1) * step;
  return `0,${chartH} ${pts} ${last},${chartH}`;
}

const lineCreated = computed(() => linePoints('created'));
const lineCompleted = computed(() => linePoints('completed'));
const areaCreated = computed(() => areaPoints('created'));
const areaCompleted = computed(() => areaPoints('completed'));

// Ресурсы менеджеров
const WORK_HOURS_MONTH = 160;
const CRM_EFFICIENCY = 0.4;

const realAvgHours = computed(() => metrics.value?.avg_processing_hours ?? 0);

const managerCapacity = computed(() => {
  const avgH = realAvgHours.value > 0 ? realAvgHours.value : 48;
  const without = Math.floor(WORK_HOURS_MONTH / avgH);
  const withCrm = Math.floor(WORK_HOURS_MONTH / (avgH * (1 - CRM_EFFICIENCY)));
  return { withoutCrm: Math.max(1, without), withCrm: Math.max(1, withCrm) };
});

const teamCapacity = computed(() => {
  const m = forecast.value.managers;
  return {
    without: m * managerCapacity.value.withoutCrm,
    with: m * managerCapacity.value.withCrm,
  };
});

const forecastRows = computed(() => {
  const f = forecast.value;
  const cap = managerCapacity.value;
  const rows = [];
  let clients = f.clientsPerMonth * 12;
  for (let y = currentYear; y <= currentYear + 4; y++) {
    const perMonth = Math.round(clients / 12);
    const managersWithout = Math.max(f.managers, Math.ceil(perMonth / Math.max(1, cap.withoutCrm)));
    const managersWith = Math.max(f.managers, Math.ceil(perMonth / Math.max(1, cap.withCrm)));
    const saved = managersWithout - managersWith;
    const revenue = Math.round(clients * f.avgCheck);
    rows.push({ year: y, clients: Math.round(clients), managersWithout, managersWith, saved, revenue });
    clients = clients * (1 + f.growthPct / 100);
  }
  return rows;
});

const maxForecastClients = computed(() => Math.max(1, ...forecastRows.value.map(r => r.clients)));
const totalSaved = computed(() => {
  const last = forecastRows.value[forecastRows.value.length - 1];
  return last?.saved ?? 0;
});

// === Воронка продаж ===
const funnelData = computed(() => {
  if (!stats.value?.cases?.by_stage) return [];
  const byStage = stats.value.cases.by_stage;
  const order = STAGES.value;
  const counts = order.map(s => ({ key: s.key, label: s.label, count: Number(byStage[s.key] ?? 0) }));
  const total = counts.reduce((s, v) => s + v.count, 0);
  if (total === 0) return [];
  return counts.map((c, i) => {
    const cumulative = counts.slice(i).reduce((s, v) => s + v.count, 0);
    const prevCumulative = i > 0 ? counts.slice(i - 1).reduce((s, v) => s + v.count, 0) : total;
    const percent = Math.round(cumulative / total * 100);
    const dropoff = i > 0 && prevCumulative > 0 ? Math.round((1 - cumulative / prevCumulative) * 100) : null;
    return { ...c, percent, dropoff };
  });
});

// === Сравнение периодов ===
const comparisonItems = computed(() => {
  const g = stats.value?.growth ?? {};
  return [
    { key: 'leads', label: t('crm.dashboard.newLeads'), growth: g.new_leads ?? 0 },
    { key: 'completed', label: t('crm.dashboard.completedPeriod'), growth: g.completed ?? 0 },
    { key: 'visa', label: t('crm.dashboard.visaIssued'), growth: g.visa_issued ?? 0 },
  ];
});

// === Цели и план ===
const goalProgressItems = computed(() => {
  if (!goalsData.value) return [];
  const progress = goalsData.value.progress ?? {};
  const goals = goalsData.value.goals ?? [];
  const yearTarget = goals.reduce((acc, g) => {
    acc.clients += g.target_clients || 0;
    acc.cases += g.target_cases || 0;
    acc.revenue += g.target_revenue || 0;
    return acc;
  }, { clients: 0, cases: 0, revenue: 0 });

  const items = [];
  if (yearTarget.clients > 0) {
    const pct = Math.round(progress.clients / yearTarget.clients * 100);
    items.push({ key: 'clients', label: t('crm.dashboard.goalClients'), current: progress.clients, target: yearTarget.clients, pct });
  }
  if (yearTarget.cases > 0) {
    const pct = Math.round(progress.cases / yearTarget.cases * 100);
    items.push({ key: 'cases', label: t('crm.dashboard.goalCases'), current: progress.cases, target: yearTarget.cases, pct });
  }
  if (yearTarget.revenue > 0) {
    const pct = Math.round(progress.revenue / yearTarget.revenue * 100);
    items.push({ key: 'revenue', label: t('crm.dashboard.goalRevenue'), current: Math.round(progress.revenue), target: yearTarget.revenue, pct });
  }
  return items;
});

async function saveGoals() {
  savingGoal.value = true;
  try {
    await dashboardApi.saveGoal({
      year: new Date().getFullYear(),
      month: null,
      ...goalForm.value,
    });
    await loadGoals();
    showGoalForm.value = false;
  } finally {
    savingGoal.value = false;
  }
}

async function loadGoals() {
  try {
    const { data } = await dashboardApi.goals({ year: new Date().getFullYear() });
    goalsData.value = data.data;
    const goals = data.data?.goals ?? [];
    if (goals.length) {
      const yearGoal = goals.find(g => !g.month) || goals[0];
      goalForm.value = {
        target_clients: yearGoal.target_clients || 0,
        target_cases: yearGoal.target_cases || 0,
        target_revenue: yearGoal.target_revenue || 0,
      };
    }
  } catch { /* ignore */ }
}

// === Лента активности ===
const ACTIVITY_DOT_CLASSES = {
  stage_change:      'bg-blue-100 text-blue-600',
  case_created:      'bg-green-100 text-green-600',
  document_uploaded: 'bg-amber-100 text-amber-600',
  payment:           'bg-purple-100 text-purple-600',
  comment:           'bg-gray-100 text-gray-600',
};

function activityDotClass(type) {
  return ACTIVITY_DOT_CLASSES[type] || 'bg-gray-100 text-gray-500';
}

async function loadActivity() {
  activityLoading.value = true;
  try {
    const { data } = await dashboardApi.activityFeed();
    activityFeed.value = data.data ?? [];
    activityLoaded.value = true;
  } finally {
    activityLoading.value = false;
  }
}

function formatDate(dt) {
  if (!dt) return '';
  const d = new Date(dt);
  const now = new Date();
  const diff = Math.floor((now - d) / 60000);
  if (diff < 1) return t('crm.dashboard.justNow');
  if (diff < 60) return `${diff} ${t('crm.dashboard.minuteShort')} ${t('crm.dashboard.ago')}`;
  if (diff < 1440) return `${Math.floor(diff / 60)} ${t('crm.dashboard.hourShort')} ${t('crm.dashboard.ago')}`;
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

// === Рейтинг менеджеров ===
function managerInitials(name) {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  return parts.length >= 2
    ? (parts[0][0] + parts[1][0]).toUpperCase()
    : name.substring(0, 2).toUpperCase();
}

function managerInitialsColor(name) {
  const colors = ['#6366f1','#8b5cf6','#ec4899','#f43f5e','#f97316','#eab308','#22c55e','#14b8a6','#3b82f6','#64748b'];
  let hash = 0;
  for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  return colors[Math.abs(hash) % colors.length];
}

const managerRanking = computed(() => {
  if (!stats.value?.managers?.length) return [];
  const all = [...stats.value.managers].map(m => ({
    ...m,
    score: (m.completed_cases * 10) + (m.conversion * 2) + (m.active_cases * 3) - (m.overdue_cases * 15),
  }));

  const maxCompleted = Math.max(...all.map(m => m.completed_cases), 0);
  const maxConversion = Math.max(...all.map(m => m.conversion), 0);
  const minAvgHours = Math.min(...all.filter(m => m.avg_hours > 0).map(m => m.avg_hours), Infinity);

  return all
    .sort((a, b) => b.score - a.score)
    .slice(0, 3)
    .map(m => {
      // Объяснение места
      const parts = [];
      if (maxCompleted > 0 && m.completed_cases === maxCompleted) parts.push(t('crm.dashboard.rankBestCompleted'));
      if (maxConversion > 0 && m.conversion === maxConversion) parts.push(t('crm.dashboard.rankHighConversion'));
      if (minAvgHours < Infinity && m.avg_hours > 0 && m.avg_hours === minAvgHours) parts.push(t('crm.dashboard.rankFastProcessing'));
      if (m.overdue_cases === 0 && m.active_cases > 0) parts.push(t('crm.dashboard.rankNoOverdue'));
      if (!parts.length) {
        if (m.completed_cases * 10 > m.conversion * 2) parts.push(t('crm.dashboard.rankGoodVolume'));
        else parts.push(t('crm.dashboard.rankStableWork'));
      }
      const explanation = parts.slice(0, 2).join(', ');

      // Награды
      const awards = [];
      if (minAvgHours < Infinity && m.avg_hours > 0 && m.avg_hours === minAvgHours) {
        awards.push({ key: 'speed', label: t('crm.dashboard.awardSpeed'), cls: 'bg-blue-50 text-blue-600', icon: 'M13 10V3L4 14h7v7l9-11h-7z' });
      }
      if (maxConversion > 0 && m.conversion === maxConversion) {
        awards.push({ key: 'quality', label: t('crm.dashboard.awardQuality'), cls: 'bg-green-50 text-green-600', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' });
      }
      if (maxCompleted > 0 && m.completed_cases === maxCompleted) {
        awards.push({ key: 'volume', label: t('crm.dashboard.awardVolume'), cls: 'bg-amber-50 text-amber-600', icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' });
      }

      return { ...m, explanation, awards };
    });
});

// === Финансовый блок ===
async function loadFinancial() {
  financialLoading.value = true;
  try {
    const { data } = await dashboardApi.financialSummary({ period: period.value });
    financialData.value = data.data;
    financialLoaded.value = true;
  } finally {
    financialLoading.value = false;
  }
}

const financialCards = computed(() => {
  if (!financialData.value) return [];
  const f = financialData.value;
  const fmt = (v) => v != null ? Number(v).toLocaleString('ru-RU').replace(/,/g, ' ') + ' ' + t('crm.dashboard.currencySuffix') : '--';
  return [
    { key: 'total', label: t('crm.dashboard.finTotal'), value: fmt(f.total_revenue), color: 'text-green-600' },
    { key: 'period', label: t('crm.dashboard.finPeriod'), value: fmt(f.period_revenue), color: 'text-blue-600' },
    { key: 'avg', label: t('crm.dashboard.finAvgCheck'), value: fmt(f.avg_check), color: 'text-indigo-600' },
    { key: 'pending', label: t('crm.dashboard.finPending'), value: fmt(f.pending_payments), color: 'text-amber-600' },
    { key: 'count', label: t('crm.dashboard.finPayments'), value: String(f.payment_count ?? 0), color: 'text-gray-800' },
    { key: 'pkg', label: t('crm.dashboard.finAvgPkg'), value: fmt(f.avg_package_price), color: 'text-purple-600' },
  ];
});

async function fetchTaskCounters() {
  try {
    const { data } = await tasksApi.counters();
    taskCounters.value = data.data ?? data;
  } catch (e) { /* ignore */ }
}

async function fetchDashboard() {
  loading.value = true;
  try {
    const { data } = await dashboardApi.index({ period: period.value });
    stats.value = data.data;
    hints.value = data.data?.hints ?? [];
    if (data.data?.managers_count && forecast.value.managers === 3) {
      forecast.value.managers = data.data.managers_count;
    }
  } finally {
    loading.value = false;
  }
}

function changePeriod(p) {
  period.value = p;
  fetchDashboard();
}

onMounted(() => {
  fetchDashboard();
  loadGoals();
  fetchTaskCounters();
});
</script>
