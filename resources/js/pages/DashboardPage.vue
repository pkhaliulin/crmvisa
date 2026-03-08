<template>
  <div class="space-y-4">
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Фильтр периода -->
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

      <!-- Ключевые метрики (кликабельные) -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <component :is="m.to ? 'router-link' : 'div'" v-for="m in metricCards" :key="m.key"
          :to="m.to || undefined"
          :class="[
            'bg-white rounded-xl border border-gray-200 px-4 py-3 transition-all group relative',
            m.to ? 'hover:border-blue-300 hover:shadow-md cursor-pointer' : ''
          ]">
          <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ m.label }}</p>
          <div class="flex items-end gap-2">
            <p class="text-2xl font-bold mt-0.5" :class="m.color">{{ m.value }}</p>
            <span v-if="m.growth !== undefined && m.growth !== 0"
              class="text-[10px] font-semibold mb-1 px-1.5 py-0.5 rounded-full"
              :class="m.growth > 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'">
              {{ m.growth > 0 ? '+' : '' }}{{ m.growth }}%
            </span>
          </div>
          <p v-if="m.sub" class="text-[10px] text-gray-400 mt-0.5">{{ m.sub }}</p>
          <!-- Тултип -->
          <div v-if="m.tooltip" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-48 text-center z-10">
            {{ m.tooltip }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
          </div>
        </component>
      </div>

      <!-- Графики row -->
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

        <!-- Pie: Источники лидов -->
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
                <span class="text-gray-400 text-[10px]">{{ totalLeads > 0 ? Math.round(s.count / totalLeads * 100) : 0 }}%</span>
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

      <!-- Заявки по этапам + Топ стран -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 lg:col-span-2">
          <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.stagesTitle') }}</h3>
          <div class="space-y-2.5">
            <router-link v-for="stage in stageRows" :key="stage.key"
              :to="{ name: 'cases', query: { stage: stage.key } }"
              class="flex items-center gap-3 group hover:bg-gray-50 rounded-lg px-1 -mx-1 py-0.5 transition-colors">
              <span class="text-xs text-gray-500 w-32 shrink-0 group-hover:text-blue-600 transition-colors">{{ stage.label }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700" :style="{ width: stage.percent + '%', background: stageColor(stage.key) }"/>
              </div>
              <span class="text-xs font-bold text-gray-700 w-6 text-right">{{ stage.count }}</span>
            </router-link>
          </div>
        </div>

        <!-- Популярные страны платформы -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="font-semibold text-gray-800 text-sm mb-1">{{ t('crm.dashboard.popularCountriesTitle') }}</h3>
          <p class="text-[10px] text-gray-400 mb-3">{{ t('crm.dashboard.popularCountriesSub') }}</p>

          <!-- Топ 5 -->
          <div v-if="topPopular.length" class="space-y-2">
            <router-link v-for="(c, i) in topPopular" :key="c.country_code"
              :to="{ name: 'countries.show', params: { code: c.country_code } }"
              class="flex items-center gap-2 text-xs group hover:bg-gray-50 rounded-lg px-1 -mx-1 py-1 transition-colors">
              <span class="font-bold text-gray-400 w-4">{{ i + 1 }}</span>
              <span class="text-sm">{{ c.flag_emoji }}</span>
              <span class="flex-1 text-gray-700 group-hover:text-blue-600 transition-colors truncate">{{ c.name }}</span>
              <span v-if="c.agency_works" class="text-[10px] font-medium px-1.5 py-0.5 bg-green-50 text-green-600 rounded-full">{{ t('crm.dashboard.works') }}</span>
              <span v-else class="text-[10px] font-medium px-1.5 py-0.5 bg-red-50 text-red-500 rounded-full">{{ t('crm.dashboard.notWorks') }}</span>
            </router-link>
          </div>

          <!-- Высокий интерес (следующие 10) -->
          <div v-if="trendingPopular.length" class="mt-4">
            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide mb-2">{{ t('crm.dashboard.highInterest') }}</p>
            <div class="space-y-1.5">
              <router-link v-for="c in trendingPopular" :key="c.country_code"
                :to="{ name: 'countries.show', params: { code: c.country_code } }"
                class="flex items-center gap-2 text-xs group hover:bg-gray-50 rounded-lg px-1 -mx-1 py-0.5 transition-colors">
                <span class="text-sm">{{ c.flag_emoji }}</span>
                <span class="flex-1 text-gray-600 group-hover:text-blue-600 transition-colors truncate">{{ c.name }}</span>
                <span v-if="c.agency_works" class="text-[9px] font-medium px-1.5 py-0.5 bg-green-50 text-green-600 rounded-full">{{ t('crm.dashboard.works') }}</span>
                <span v-else class="text-[9px] font-medium px-1.5 py-0.5 bg-red-50 text-red-500 rounded-full">{{ t('crm.dashboard.notWorks') }}</span>
              </router-link>
            </div>
          </div>

          <div v-if="!topPopular.length && !trendingPopular.length" class="text-sm text-gray-400 text-center py-8">{{ t('crm.dashboard.noData') }}</div>
        </div>
      </div>

      <!-- Скорость обработки по этапам (SLA) -->
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
        <div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-gray-500 text-[11px] uppercase tracking-wide">
              <tr>
                <th class="text-left px-4 py-2.5 font-medium">{{ t('crm.dashboard.stageCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">
                  <span class="group relative cursor-help inline-flex items-center gap-1">
                    {{ t('crm.dashboard.slaNorm') }}
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
                    <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-52 text-left z-50 normal-case tracking-normal font-normal">
                      {{ t('crm.dashboard.slaNormTooltip') }}
                      <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                    </div>
                  </span>
                </th>
                <th class="text-right px-4 py-2.5 font-medium">
                  <span class="group relative cursor-help inline-flex items-center gap-1">
                    {{ t('crm.dashboard.avgTime') }}
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
                    <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-52 text-left z-50 normal-case tracking-normal font-normal">
                      {{ t('crm.dashboard.avgTimeTooltip') }}
                      <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                    </div>
                  </span>
                </th>
                <th class="text-right px-4 py-2.5 font-medium">
                  <span class="group relative cursor-help inline-flex items-center gap-1">
                    {{ t('crm.dashboard.deviation') }}
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
                    <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-52 text-left z-50 normal-case tracking-normal font-normal">
                      {{ t('crm.dashboard.deviationTooltip') }}
                      <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                    </div>
                  </span>
                </th>
                <th class="text-right px-4 py-2.5 font-medium">
                  <span class="group relative cursor-help inline-flex items-center gap-1">
                    {{ t('crm.dashboard.slaCompliance') }}
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
                    <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-52 text-left z-50 normal-case tracking-normal font-normal">
                      {{ t('crm.dashboard.slaComplianceTooltip') }}
                      <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                    </div>
                  </span>
                </th>
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

      <!-- Менеджеры -->
      <div v-if="stats?.managers?.length" class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.managersLoad') }}</h3>
        <div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-gray-500 text-[11px] uppercase tracking-wide">
              <tr>
                <th class="text-left px-4 py-2.5 font-medium">{{ t('crm.dashboard.managerCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">
                  <span class="group relative cursor-help inline-flex items-center gap-1">
                    {{ t('crm.dashboard.activeCol') }}
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
                    <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-52 text-left z-50 normal-case tracking-normal font-normal">
                      {{ t('crm.dashboard.activeColTooltip') }}
                      <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                    </div>
                  </span>
                </th>
                <th class="text-right px-4 py-2.5 font-medium">
                  <span class="group relative cursor-help inline-flex items-center gap-1">
                    {{ t('crm.dashboard.completedCol') }}
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
                    <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-52 text-left z-50 normal-case tracking-normal font-normal">
                      {{ t('crm.dashboard.completedColTooltip') }}
                      <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                    </div>
                  </span>
                </th>
                <th class="text-right px-4 py-2.5 font-medium">
                  <span class="group relative cursor-help inline-flex items-center gap-1">
                    {{ t('crm.dashboard.overdueCol') }}
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
                    <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-52 text-left z-50 normal-case tracking-normal font-normal">
                      {{ t('crm.dashboard.overdueColTooltip') }}
                      <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                    </div>
                  </span>
                </th>
                <th class="text-right px-4 py-2.5 font-medium">
                  <span class="group relative cursor-help inline-flex items-center gap-1">
                    {{ t('crm.dashboard.avgTimeCol') }}
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
                    <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-52 text-left z-50 normal-case tracking-normal font-normal">
                      {{ t('crm.dashboard.avgTimeColTooltip') }}
                      <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                    </div>
                  </span>
                </th>
                <th class="text-right px-4 py-2.5 font-medium">
                  <span class="group relative cursor-help inline-flex items-center gap-1">
                    {{ t('crm.dashboard.conversionCol') }}
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
                    <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-52 text-left z-50 normal-case tracking-normal font-normal">
                      {{ t('crm.dashboard.conversionColTooltip') }}
                      <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                    </div>
                  </span>
                </th>
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

      <!-- Конверсии + Среднее время + Повторные клиенты -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 group relative">
          <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.conversionLeadCase') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-blue-600">{{ metrics.conversion_lead_case }}%</p>
          </div>
          <div class="mt-3 bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="h-full bg-blue-500 rounded-full transition-all" :style="{ width: metrics.conversion_lead_case + '%' }"/>
          </div>
          <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-56 text-center z-10">
            {{ t('crm.dashboard.conversionLeadCaseTooltip') }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 group relative">
          <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.conversionCaseVisa') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-green-600">{{ metrics.conversion_case_visa }}%</p>
          </div>
          <div class="mt-3 bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: metrics.conversion_case_visa + '%' }"/>
          </div>
          <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-56 text-center z-10">
            {{ t('crm.dashboard.conversionCaseVisaTooltip') }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 group relative">
          <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.avgProcessing') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-indigo-600">{{ formatHours(metrics.avg_processing_hours) }}</p>
          </div>
          <p class="text-[10px] text-gray-400 mt-1">{{ t('crm.dashboard.avgProcessingSub') }}</p>
          <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-56 text-center z-10">
            {{ t('crm.dashboard.avgProcessingTooltip') }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
          </div>
        </div>
        <router-link :to="{ name: 'clients' }" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 hover:shadow-md transition-all group relative">
          <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.repeatClients') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-purple-600">{{ stats?.repeat_clients ?? 0 }}</p>
            <span v-if="stats?.clients_total" class="text-xs text-gray-400 mb-1">/ {{ stats.clients_total }}</span>
          </div>
          <p class="text-[10px] text-gray-400 mt-1">{{ t('crm.dashboard.repeatClientsSub') }}</p>
          <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none w-56 text-center z-10">
            {{ t('crm.dashboard.repeatClientsTooltip') }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
          </div>
        </router-link>
      </div>

      <!-- Калькулятор прогноза роста -->
      <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl border border-indigo-200 p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="font-semibold text-indigo-900 text-sm">{{ t('crm.forecast.title') }}</h3>
            <p class="text-[10px] text-indigo-600 mt-0.5">{{ t('crm.forecast.subtitle') }}</p>
          </div>
          <button @click="showForecast = !showForecast"
            class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
            :class="showForecast ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600 border border-indigo-300 hover:bg-indigo-100'">
            {{ showForecast ? t('crm.forecast.hide') : t('crm.forecast.show') }}
          </button>
        </div>

        <div v-if="showForecast" class="space-y-4">
          <!-- Ввод данных -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div>
              <label class="text-[10px] font-medium text-indigo-700 uppercase tracking-wide">{{ t('crm.forecast.clientsPerMonth') }}</label>
              <input v-model.number="forecast.clientsPerMonth" type="number" min="1" max="10000"
                class="mt-1 w-full px-3 py-2 text-sm border border-indigo-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
              <label class="text-[10px] font-medium text-indigo-700 uppercase tracking-wide">{{ t('crm.forecast.avgCheck') }}</label>
              <input v-model.number="forecast.avgCheck" type="number" min="10" max="100000"
                class="mt-1 w-full px-3 py-2 text-sm border border-indigo-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
              <label class="text-[10px] font-medium text-indigo-700 uppercase tracking-wide">{{ t('crm.forecast.managers') }}</label>
              <input v-model.number="forecast.managers" type="number" min="1" max="100"
                class="mt-1 w-full px-3 py-2 text-sm border border-indigo-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
              <label class="text-[10px] font-medium text-indigo-700 uppercase tracking-wide">{{ t('crm.forecast.growthPct') }}</label>
              <input v-model.number="forecast.growthPct" type="number" min="0" max="300"
                class="mt-1 w-full px-3 py-2 text-sm border border-indigo-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
          </div>

          <!-- Ресурсы менеджеров: реальные данные -->
          <div class="bg-white/60 rounded-lg p-4 border border-indigo-100">
            <p class="text-[10px] font-medium text-indigo-700 uppercase tracking-wide mb-3">{{ t('crm.forecast.managerCapacity') }}</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
              <div>
                <p class="text-lg font-bold text-gray-900">{{ stats?.managers_count ?? 0 }}</p>
                <p class="text-[10px] text-gray-500">{{ t('crm.forecast.currentManagers') }}</p>
              </div>
              <div>
                <p class="text-lg font-bold" :class="realAvgHours > 0 ? 'text-gray-900' : 'text-gray-400'">{{ realAvgHours > 0 ? formatHours(realAvgHours) : '--' }}</p>
                <p class="text-[10px] text-gray-500">{{ t('crm.forecast.realAvgTime') }}</p>
              </div>
              <div>
                <p class="text-lg font-bold text-blue-600">{{ managerCapacity.withoutCrm }}</p>
                <p class="text-[10px] text-gray-500">{{ t('crm.forecast.capacityWithout') }}</p>
              </div>
              <div>
                <p class="text-lg font-bold text-green-600">{{ managerCapacity.withCrm }}</p>
                <p class="text-[10px] text-gray-500">{{ t('crm.forecast.capacityWith') }}</p>
              </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3">
              <div class="bg-gray-50 rounded-lg p-2.5 text-center">
                <p class="text-[10px] text-gray-500">{{ t('crm.forecast.totalCapacityWithout') }}</p>
                <p class="text-sm font-bold text-gray-700">{{ teamCapacity.without }} {{ t('crm.forecast.clientsMonth') }}</p>
              </div>
              <div class="bg-green-50 rounded-lg p-2.5 text-center">
                <p class="text-[10px] text-green-600">{{ t('crm.forecast.totalCapacityWith') }}</p>
                <p class="text-sm font-bold text-green-700">{{ teamCapacity.with }} {{ t('crm.forecast.clientsMonth') }}</p>
              </div>
            </div>
          </div>

          <!-- Прогноз по годам -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-white/60 border-b text-indigo-700 text-[10px] uppercase tracking-wide">
                <tr>
                  <th class="text-left px-3 py-2 font-medium">{{ t('crm.forecast.yearCol') }}</th>
                  <th class="text-right px-3 py-2 font-medium">{{ t('crm.forecast.clientsCol') }}</th>
                  <th class="text-right px-3 py-2 font-medium cursor-help" :title="t('crm.forecast.managersWithoutTooltip')">{{ t('crm.forecast.managersWithout') }} <span class="text-indigo-300">?</span></th>
                  <th class="text-right px-3 py-2 font-medium cursor-help" :title="t('crm.forecast.managersWithTooltip')">{{ t('crm.forecast.managersWith') }} <span class="text-indigo-300">?</span></th>
                  <th class="text-right px-3 py-2 font-medium cursor-help" :title="t('crm.forecast.savingsTooltip')">{{ t('crm.forecast.savings') }} <span class="text-indigo-300">?</span></th>
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

          <!-- Мотивационное сообщение -->
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
              <p class="text-[10px] text-green-600 mt-1">{{ t('crm.forecast.automationSavingSub') }}</p>
            </div>
            <p class="text-xs text-indigo-600 mt-3 text-center">{{ t('crm.forecast.motivationSub') }}</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { dashboardApi } from '@/api/dashboard';

const { t } = useI18n();

const loading = ref(true);
const stats = ref(null);
const hints = ref([]);
const period = ref('30d');
const showForecast = ref(false);
const forecast = ref({
  clientsPerMonth: 20,
  avgCheck: 150,
  managers: 3,
  growthPct: 30,
});

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
const WORK_HOURS_MONTH = 160; // рабочих часов в месяце
const CRM_EFFICIENCY = 0.4; // CRM сокращает время обработки на 40%

const realAvgHours = computed(() => metrics.value?.avg_processing_hours ?? 0);

// Сколько клиентов 1 менеджер обработает в месяц
const managerCapacity = computed(() => {
  const avgH = realAvgHours.value > 0 ? realAvgHours.value : 48; // default 48ч если нет данных
  const without = Math.floor(WORK_HOURS_MONTH / avgH);
  const withCrm = Math.floor(WORK_HOURS_MONTH / (avgH * (1 - CRM_EFFICIENCY)));
  return { withoutCrm: Math.max(1, without), withCrm: Math.max(1, withCrm) };
});

// Общая пропускная способность команды
const teamCapacity = computed(() => {
  const m = forecast.value.managers;
  return {
    without: m * managerCapacity.value.withoutCrm,
    with: m * managerCapacity.value.withCrm,
  };
});

// Прогноз роста
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

async function fetchDashboard() {
  loading.value = true;
  try {
    const { data } = await dashboardApi.index({ period: period.value });
    stats.value = data.data;
    hints.value = data.data?.hints ?? [];
    // Автозаполнение кол-ва менеджеров из реальных данных (только при первой загрузке)
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

onMounted(fetchDashboard);
</script>
