<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-bold text-gray-900">{{ t('owner.billing.title') }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ t('owner.billing.subtitle') }}</p>
    </div>

    <!-- Табы -->
    <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
      <button v-for="tab in tabItems" :key="tab.key"
        @click="activeTab = tab.key"
        :class="['px-4 py-2 text-sm font-medium rounded-md transition-colors',
          activeTab === tab.key
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-600 hover:text-gray-900']">
        {{ tab.label }}
      </button>
    </div>

    <!-- =============== ОБЗОР =============== -->
    <div v-if="activeTab === 'dashboard'" class="space-y-6">
      <div v-if="dashLoading" class="flex justify-center py-16">
        <div class="w-8 h-8 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
      </div>
      <template v-else-if="dash">
        <!-- KPI карточки -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="text-xs text-gray-500 leading-tight">{{ t('owner.billing.totalRevenue') }}<br/>{{ t('owner.billing.allTime') }}</div>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ fmtMoney(dash.revenue?.total_revenue) }}</div>
          </div>
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
              </div>
              <div class="text-xs text-gray-500 leading-tight">{{ t('owner.billing.revenueThisMonth') }}<br/>{{ t('owner.billing.thisMonth') }}</div>
            </div>
            <div class="text-2xl font-bold text-green-600">{{ fmtMoney(dash.revenue_this_month) }}</div>
          </div>
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
              </div>
              <div class="text-xs text-gray-500 leading-tight">{{ t('owner.billing.vatToPay') }}<br/>{{ t('owner.billing.vatToPaySub') }}</div>
            </div>
            <div class="text-2xl font-bold text-orange-600">{{ fmtMoney(dash.revenue?.total_vat) }}</div>
          </div>
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                :class="dash.overdue_invoices > 0 ? 'bg-red-50' : 'bg-gray-50'">
                <svg class="w-5 h-5" :class="dash.overdue_invoices > 0 ? 'text-red-600' : 'text-gray-400'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
              </div>
              <div class="text-xs text-gray-500 leading-tight">{{ t('owner.billing.overdueInvoices') }}<br/>{{ t('owner.billing.overdueInvoicesSub') }}</div>
            </div>
            <div class="text-2xl font-bold" :class="dash.overdue_invoices > 0 ? 'text-red-600' : 'text-gray-300'">{{ dash.overdue_invoices || 0 }}</div>
          </div>
        </div>

        <!-- Подписки и распределение -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ t('owner.billing.agencySubscriptions') }}</h3>
            <p class="text-xs text-gray-400 mb-4">{{ t('owner.billing.agencySubscriptionsDesc') }}</p>
            <div v-for="(count, status) in dash.subscriptions" :key="status"
              class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
              <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full" :class="statusDotColor(status)"></div>
                <span class="text-sm text-gray-700">{{ statusLabel(status) }}</span>
              </div>
              <span class="text-sm font-bold text-gray-900 bg-gray-100 px-2.5 py-0.5 rounded-full">{{ count }}</span>
            </div>
            <div v-if="!Object.keys(dash.subscriptions || {}).length"
              class="text-sm text-gray-400 text-center py-4">{{ t('owner.billing.noSubscriptions') }}</div>
          </div>
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ t('owner.billing.byPlans') }}</h3>
            <p class="text-xs text-gray-400 mb-4">{{ t('owner.billing.byPlansDesc') }}</p>
            <div v-for="(count, plan) in dash.plan_distribution" :key="plan"
              class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
              <span class="text-sm text-gray-700 capitalize">{{ planDisplayName(plan) }}</span>
              <span class="text-sm font-bold text-gray-900 bg-gray-100 px-2.5 py-0.5 rounded-full">{{ count }}</span>
            </div>
            <div v-if="!Object.keys(dash.plan_distribution || {}).length"
              class="text-sm text-gray-400 text-center py-4">{{ t('owner.billing.noActivePlans') }}</div>
          </div>
        </div>

        <!-- Последние события -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ t('owner.billing.recentEvents') }}</h3>
          <p class="text-xs text-gray-400 mb-4">{{ t('owner.billing.recentEventsDesc') }}</p>
          <div class="space-y-0 max-h-80 overflow-y-auto">
            <div v-for="ev in dash.recent_events" :key="ev.id"
              class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-0">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                :class="eventIconBg(ev.event)">
                <span class="text-sm">{{ eventIcon(ev.event) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-900">{{ eventLabel(ev.event) }}</div>
                <div class="text-xs text-gray-400">{{ ev.agency_name || t('owner.billing.system') }}</div>
              </div>
              <div v-if="ev.amount" class="text-sm font-bold text-gray-900 shrink-0">{{ fmtMoney(ev.amount) }}</div>
              <div class="text-xs text-gray-400 shrink-0 w-28 text-right">{{ formatDate(ev.created_at) }}</div>
            </div>
            <div v-if="!dash.recent_events?.length"
              class="text-sm text-gray-400 text-center py-6">{{ t('owner.billing.noEvents') }}</div>
          </div>
        </div>
      </template>
    </div>

    <!-- =============== ТАРИФЫ =============== -->
    <div v-if="activeTab === 'plans'" class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400">{{ t('owner.billing.plansDesc') }}</p>
        </div>
        <button @click="openPlanModal(null)"
          class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-medium rounded-lg hover:bg-[#0d2a5e] transition-colors">
          {{ t('owner.billing.newPlan') }}
        </button>
      </div>

      <div v-if="plansLoading" class="flex justify-center py-16">
        <div class="w-8 h-8 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div v-for="p in plans" :key="p.slug"
          :class="['bg-white rounded-xl border-2 p-5 relative transition-all',
            p.is_recommended ? 'border-[#1BA97F] shadow-lg shadow-[#1BA97F]/10' : 'border-gray-200',
            !p.is_active ? 'opacity-50 grayscale' : '']">

          <!-- Бейдж рекомендуемого -->
          <div v-if="p.is_recommended"
            class="absolute -top-3 left-1/2 -translate-x-1/2 text-[10px] bg-[#1BA97F] text-white px-3 py-0.5 rounded-full font-bold whitespace-nowrap">
            {{ t('owner.billing.recommended') }}
          </div>

          <!-- Заголовок -->
          <div class="mb-4">
            <div class="text-lg font-bold text-gray-900">{{ p.name }}</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">
              {{ p.price_uzs > 0 ? fmtMoney(p.price_uzs) : t('owner.billing.free') }}
              <span v-if="p.price_uzs > 0" class="text-sm font-normal text-gray-400"> {{ t('owner.billing.perMonth') }}</span>
            </div>
            <div v-if="p.description" class="text-xs text-gray-400 mt-1">{{ p.description }}</div>
          </div>

          <!-- Лимиты -->
          <div class="space-y-2 text-sm border-t border-gray-100 pt-4">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <span class="text-gray-600">{{ t('owner.billing.managers') }}: <b>{{ p.max_managers === 0 ? t('owner.billing.noLimit') : p.max_managers }}</b></span>
            </div>
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span class="text-gray-600">{{ t('owner.billing.cases') }}: <b>{{ p.max_cases === 0 ? t('owner.billing.noLimit') : p.max_cases }}</b></span>
            </div>
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <span class="text-gray-600">{{ t('owner.billing.leadsPerMonth') }}: <b>{{ p.max_leads_per_month === 0 ? t('owner.billing.noLimit') : p.max_leads_per_month }}</b></span>
            </div>
          </div>

          <!-- Условия -->
          <div v-if="p.activation_fee_uzs > 0 || p.earn_first_enabled || p.trial_days > 0 || p.grace_period_days > 0"
            class="space-y-1.5 text-xs border-t border-gray-100 pt-3 mt-3">
            <div v-if="p.trial_days > 0" class="flex items-center gap-2 text-blue-700">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ t('owner.billing.trialDays', { days: p.trial_days }) }}
            </div>
            <div v-if="p.activation_fee_uzs > 0" class="flex items-center gap-2 text-gray-600">
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
              </svg>
              {{ t('owner.billing.activationFee', { amount: fmtMoney(p.activation_fee_uzs) }) }}
            </div>
            <div v-if="p.earn_first_enabled" class="flex items-center gap-2 text-green-700">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
              </svg>
              {{ t('owner.billing.earnFirst', { pct: p.earn_first_deduction_pct }) }}
            </div>
            <div v-if="p.grace_period_days > 0" class="flex items-center gap-2 text-gray-500">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ t('owner.billing.gracePeriod', { days: p.grace_period_days }) }}
            </div>
          </div>

          <!-- Возможности -->
          <div v-if="planFeatures(p).length" class="flex flex-wrap gap-1 border-t border-gray-100 pt-3 mt-3">
            <span v-for="f in planFeatures(p)" :key="f.label"
              :class="['text-[10px] font-medium px-2 py-0.5 rounded-full', f.class]">
              {{ f.label }}
            </span>
          </div>

          <!-- Подписчики -->
          <div class="text-xs text-gray-400 mt-3 pt-3 border-t border-gray-100">
            {{ t('owner.billing.usedBy', { count: p.subscribers_count || 0 }) }}
          </div>

          <!-- Действия -->
          <div class="mt-4 flex gap-2">
            <button @click="openPlanModal(p)"
              class="flex-1 px-3 py-2 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
              {{ t('owner.billing.editPlanBtn') }}
            </button>
            <button v-if="p.is_active" @click="deactivatePlan(p.slug)"
              class="px-3 py-2 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
              {{ t('owner.billing.deactivate') }}
            </button>
            <button v-else @click="activatePlan(p.slug)"
              class="px-3 py-2 text-xs font-medium text-green-600 border border-green-200 rounded-lg hover:bg-green-50 transition-colors">
              {{ t('owner.billing.activate') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- =============== НАСТРОЙКИ =============== -->
    <div v-if="activeTab === 'settings'" class="space-y-4">
      <p class="text-xs text-gray-400">{{ t('owner.billing.settingsDesc') }}</p>
      <div v-if="settingsLoading" class="flex justify-center py-16">
        <div class="w-8 h-8 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
      </div>
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Налоги -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="text-sm font-bold text-gray-900 mb-1">{{ t('owner.billing.taxTitle') }}</h3>
          <p class="text-xs text-gray-400 mb-4">{{ t('owner.billing.taxDesc') }}</p>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm font-medium text-gray-700">{{ t('owner.billing.vatEnabled') }}</div>
                <div class="text-xs text-gray-400">{{ t('owner.billing.vatEnabledDesc') }}</div>
              </div>
              <button @click="toggleSetting('vat_enabled')"
                :class="['w-12 h-6 rounded-full transition-colors relative',
                  getSettingValue('vat_enabled') ? 'bg-green-500' : 'bg-gray-300']">
                <span :class="['absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-all',
                  getSettingValue('vat_enabled') ? 'left-[26px]' : 'left-0.5']" />
              </button>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.billing.vatRate') }}</label>
              <p class="text-xs text-gray-400 mb-1">{{ t('owner.billing.vatRateDesc') }}</p>
              <input v-model="settingsMap.vat_rate" type="number"
                class="w-32 px-3 py-2 text-sm border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
            </div>
          </div>
        </div>

        <!-- Комиссии -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="text-sm font-bold text-gray-900 mb-1">{{ t('owner.billing.commissionsTitle') }}</h3>
          <p class="text-xs text-gray-400 mb-4">{{ t('owner.billing.commissionsDesc') }}</p>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.billing.platformCommission') }}</label>
              <p class="text-xs text-gray-400 mb-1">{{ t('owner.billing.platformCommissionDesc') }}</p>
              <input v-model="settingsMap.platform_commission" type="number" step="0.1"
                class="w-32 px-3 py-2 text-sm border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Click, %</label>
                <input v-model="settingsMap.click_fee_pct" type="number" step="0.1"
                  class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Payme, %</label>
                <input v-model="settingsMap.payme_fee_pct" type="number" step="0.1"
                  class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Uzum, %</label>
                <input v-model="settingsMap.uzum_fee_pct" type="number" step="0.1"
                  class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
              </div>
            </div>
          </div>
        </div>

        <!-- Выплаты -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="text-sm font-bold text-gray-900 mb-1">{{ t('owner.billing.payoutsTitle') }}</h3>
          <p class="text-xs text-gray-400 mb-4">{{ t('owner.billing.payoutsDesc') }}</p>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.billing.payoutMinAmount') }}</label>
              <p class="text-xs text-gray-400 mb-1">{{ t('owner.billing.payoutMinAmountDesc') }}</p>
              <input v-model="settingsMap.payout_min_amount" type="number"
                class="w-40 px-3 py-2 text-sm border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.billing.payoutCycleDays') }}</label>
              <p class="text-xs text-gray-400 mb-1">{{ t('owner.billing.payoutCycleDaysDesc') }}</p>
              <input v-model="settingsMap.payout_cycle_days" type="number"
                class="w-32 px-3 py-2 text-sm border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
            </div>
          </div>
        </div>

        <!-- Повторные списания -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="text-sm font-bold text-gray-900 mb-1">{{ t('owner.billing.retryTitle') }}</h3>
          <p class="text-xs text-gray-400 mb-4">{{ t('owner.billing.retryDesc') }}</p>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.billing.retryCount') }}</label>
              <p class="text-xs text-gray-400 mb-1">{{ t('owner.billing.retryCountDesc') }}</p>
              <input v-model="settingsMap.dunning_max_retries" type="number"
                class="w-32 px-3 py-2 text-sm border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.billing.retryInterval') }}</label>
              <input v-model="settingsMap.dunning_retry_days" type="number"
                class="w-32 px-3 py-2 text-sm border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button @click="saveSettings" :disabled="settingsSaving"
          class="px-6 py-2.5 bg-[#0A1F44] text-white text-sm font-medium rounded-lg hover:bg-[#0d2a5e] disabled:opacity-50 transition-colors">
          {{ settingsSaving ? t('owner.billing.saving') : t('owner.billing.saveSettings') }}
        </button>
        <span v-if="settingsSaved" class="text-sm text-green-600 font-medium">{{ t('owner.billing.saved') }}</span>
      </div>
    </div>

    <!-- =============== ПРОМОКОДЫ =============== -->
    <div v-if="activeTab === 'coupons'" class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400">{{ t('owner.billing.couponsDesc') }}</p>
        </div>
        <button @click="openCouponModal(null)"
          class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-medium rounded-lg hover:bg-[#0d2a5e] transition-colors">
          {{ t('owner.billing.newCoupon') }}
        </button>
      </div>

      <div v-if="couponsLoading" class="flex justify-center py-16">
        <div class="w-8 h-8 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
      </div>
      <div v-else-if="!coupons.length" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
          <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
          </svg>
        </div>
        <div class="text-sm text-gray-400">{{ t('owner.billing.noCoupons') }}</div>
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="c in coupons" :key="c.id"
          :class="['bg-white rounded-xl border-2 p-5 relative',
            c.is_active ? 'border-gray-200' : 'border-gray-100 opacity-60']">
          <!-- Код -->
          <div class="flex items-center justify-between mb-3">
            <span class="font-mono font-bold text-lg text-gray-900 tracking-wide">{{ c.code }}</span>
            <span :class="['text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider',
              c.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500']">
              {{ c.is_active ? t('owner.billing.couponActive') : t('owner.billing.couponDisabled') }}
            </span>
          </div>
          <!-- Описание -->
          <div v-if="c.description" class="text-xs text-gray-400 mb-3">{{ c.description }}</div>
          <!-- Скидка -->
          <div class="flex items-center gap-2 mb-3">
            <div class="text-2xl font-bold" :class="c.is_active ? 'text-[#1BA97F]' : 'text-gray-400'">
              {{ c.discount_type === 'percentage' ? c.discount_value + '%' : fmtMoney(c.discount_value) }}
            </div>
            <div class="text-xs text-gray-400">{{ t('owner.billing.discount') }}</div>
          </div>
          <!-- Инфо -->
          <div class="space-y-1.5 text-xs text-gray-500 border-t border-gray-100 pt-3">
            <div class="flex justify-between">
              <span>{{ t('owner.billing.used') }}</span>
              <span class="font-medium text-gray-700">{{ c.used_count }}{{ c.max_uses > 0 ? ' ' + t('owner.billing.usedOf') + ' ' + c.max_uses : '' }}</span>
            </div>
            <div class="flex justify-between">
              <span>{{ t('owner.billing.validUntil') }}</span>
              <span class="font-medium text-gray-700">{{ c.valid_until ? formatDateShort(c.valid_until) : t('owner.billing.unlimited') }}</span>
            </div>
          </div>
          <!-- Действие -->
          <button @click="openCouponModal(c)"
            class="mt-3 w-full px-3 py-2 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            {{ t('owner.billing.editPlanBtn') }}
          </button>
        </div>
      </div>
    </div>

    <!-- =============== МОДАЛКА ТАРИФА =============== -->
    <div v-if="showPlanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showPlanModal = false">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
        <h2 class="text-lg font-bold mb-1">{{ editPlan ? t('owner.billing.editPlanTitle') : t('owner.billing.newPlanTitle') }}</h2>
        <p class="text-xs text-gray-400 mb-4">{{ t('owner.billing.planFormDesc') }}</p>

        <!-- Основное -->
        <div class="space-y-4">
          <div class="bg-gray-50 rounded-xl p-4 space-y-3">
            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ t('owner.billing.sectionMain') }}</div>
            <div class="grid grid-cols-2 gap-3">
              <div v-if="!editPlan">
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.slugLabel') }}</label>
                <input v-model="planForm.slug" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="my-plan" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.nameLabel') }}</label>
                <input v-model="planForm.name" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.nameUzLabel') }}</label>
                <input v-model="planForm.name_uz" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div :class="editPlan ? 'col-span-2' : ''">
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.descriptionLabel') }}</label>
                <input v-model="planForm.description" class="w-full px-3 py-2 border rounded-lg text-sm" :placeholder="t('owner.billing.descriptionPlaceholder')" />
              </div>
            </div>
          </div>

          <!-- Цена -->
          <div class="bg-gray-50 rounded-xl p-4 space-y-3">
            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ t('owner.billing.sectionPrice') }}</div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.monthlyPriceUzs') }}</label>
                <input v-model.number="planForm.price_uzs" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.activationFeeUzs') }}</label>
                <input v-model.number="planForm.activation_fee_uzs" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.monthlyPriceUsd') }}</label>
                <input v-model.number="planForm.price_monthly" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.yearlyPriceUsd') }}</label>
                <input v-model.number="planForm.price_yearly" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
            </div>
          </div>

          <!-- Лимиты -->
          <div class="bg-gray-50 rounded-xl p-4 space-y-3">
            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ t('owner.billing.sectionLimits') }}</div>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.managersLabel') }}</label>
                <input v-model.number="planForm.max_managers" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.casesLabel') }}</label>
                <input v-model.number="planForm.max_cases" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.leadsLabel') }}</label>
                <input v-model.number="planForm.max_leads_per_month" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
            </div>
          </div>

          <!-- Условия -->
          <div class="bg-gray-50 rounded-xl p-4 space-y-3">
            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ t('owner.billing.sectionConditions') }}</div>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.trialDaysLabel') }}</label>
                <input v-model.number="planForm.trial_days" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.gracePeriodLabel') }}</label>
                <input v-model.number="planForm.grace_period_days" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.autoDeductLabel') }}</label>
                <input v-model.number="planForm.earn_first_deduction_pct" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
            </div>
          </div>

          <!-- Чекбоксы -->
          <div class="bg-gray-50 rounded-xl p-4 space-y-3">
            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ t('owner.billing.sectionFeatures') }}</div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                <input type="checkbox" v-model="planForm.earn_first_enabled" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
                <span>{{ t('owner.billing.earnFirstLabel') }}</span>
              </label>
              <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                <input type="checkbox" v-model="planForm.has_marketplace" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
                <span>{{ t('owner.billing.marketplace') }}</span>
              </label>
              <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                <input type="checkbox" v-model="planForm.has_analytics" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
                <span>{{ t('owner.billing.analytics') }}</span>
              </label>
              <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                <input type="checkbox" v-model="planForm.has_api_access" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
                <span>{{ t('owner.billing.apiAccess') }}</span>
              </label>
              <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                <input type="checkbox" v-model="planForm.has_white_label" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
                <span>{{ t('owner.billing.whiteLabel') }}</span>
              </label>
              <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                <input type="checkbox" v-model="planForm.has_custom_domain" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
                <span>{{ t('owner.billing.customDomain') }}</span>
              </label>
              <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                <input type="checkbox" v-model="planForm.has_priority_support" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
                <span>{{ t('owner.billing.prioritySupport') }}</span>
              </label>
            </div>
            <div class="flex gap-4 pt-2 border-t border-gray-200">
              <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" v-model="planForm.is_active" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
                <span class="font-medium">{{ t('owner.billing.isActiveLabel') }}</span>
              </label>
              <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" v-model="planForm.is_public" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
                <span class="font-medium">{{ t('owner.billing.isPublicLabel') }}</span>
              </label>
              <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" v-model="planForm.is_recommended" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
                <span class="font-medium">{{ t('owner.billing.isRecommendedLabel') }}</span>
              </label>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="showPlanModal = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">{{ t('common.cancel') }}</button>
          <button @click="savePlan" :disabled="planSaving"
            class="px-6 py-2 bg-[#0A1F44] text-white text-sm font-medium rounded-lg hover:bg-[#0d2a5e] disabled:opacity-50 transition-colors">
            {{ planSaving ? t('owner.billing.saving') : t('common.save') }}
          </button>
        </div>
      </div>
    </div>

    <!-- =============== МОДАЛКА ПРОМОКОДА =============== -->
    <div v-if="showCouponModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showCouponModal = false">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-bold mb-1">{{ editCoupon ? t('owner.billing.editCouponTitle') : t('owner.billing.newCouponTitle') }}</h2>
        <p class="text-xs text-gray-400 mb-4">{{ t('owner.billing.couponFormDesc') }}</p>
        <div class="space-y-3">
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.codeLabel') }}</label>
            <input v-model="couponForm.code" :disabled="!!editCoupon"
              class="w-full px-3 py-2 border rounded-lg text-sm font-mono uppercase"
              placeholder="WELCOME2026" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.couponDescLabel') }}</label>
            <input v-model="couponForm.description" class="w-full px-3 py-2 border rounded-lg text-sm"
              :placeholder="t('owner.billing.couponDescPlaceholder')" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.discountType') }}</label>
              <select v-model="couponForm.discount_type" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="percentage">{{ t('owner.billing.discountPercentage') }}</option>
                <option value="fixed">{{ t('owner.billing.discountFixed') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ couponForm.discount_type === 'percentage' ? t('owner.billing.discountPercentLabel') : t('owner.billing.discountFixedLabel') }}</label>
              <input v-model.number="couponForm.discount_value" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.maxUses') }}</label>
              <input v-model.number="couponForm.max_uses" type="number" class="w-full px-3 py-2 border rounded-lg text-sm"
                :placeholder="t('owner.billing.maxUsesPlaceholder')" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ t('owner.billing.validUntilLabel') }}</label>
              <input v-model="couponForm.valid_until" type="date" class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
          </div>
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" v-model="couponForm.is_active" class="rounded text-[#1BA97F] focus:ring-[#1BA97F]" />
            <span>{{ t('owner.billing.couponIsActive') }}</span>
          </label>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button @click="showCouponModal = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">{{ t('common.cancel') }}</button>
          <button @click="saveCoupon" :disabled="couponSaving"
            class="px-6 py-2 bg-[#0A1F44] text-white text-sm font-medium rounded-lg hover:bg-[#0d2a5e] disabled:opacity-50 transition-colors">
            {{ couponSaving ? t('owner.billing.saving') : t('common.save') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';

const { t } = useI18n();

const tabItems = computed(() => [
  { key: 'dashboard', label: t('owner.billing.tabDashboard') },
  { key: 'plans',     label: t('owner.billing.tabPlans') },
  { key: 'settings',  label: t('owner.billing.tabSettings') },
  { key: 'coupons',   label: t('owner.billing.tabCoupons') },
]);
const activeTab = ref('dashboard');

// ======================== DASHBOARD ========================
const dash = ref(null);
const dashLoading = ref(false);
async function loadDashboard() {
  dashLoading.value = true;
  try {
    const { data } = await api.get('/owner/billing/dashboard');
    dash.value = data.data;
  } catch { /* silent */ }
  dashLoading.value = false;
}

// ======================== PLANS ========================
const plans = ref([]);
const plansLoading = ref(false);
const showPlanModal = ref(false);
const editPlan = ref(null);
const planSaving = ref(false);
const planForm = ref({});

async function loadPlans() {
  plansLoading.value = true;
  try {
    const { data } = await api.get('/owner/billing/plans');
    plans.value = data.data;
  } catch { /* silent */ }
  plansLoading.value = false;
}

function defaultPlanForm() {
  return {
    slug: '', name: '', name_uz: '', description: '',
    price_monthly: 0, price_yearly: 0, price_uzs: 0,
    activation_fee_uzs: 0, earn_first_enabled: false, earn_first_deduction_pct: 0,
    max_managers: 3, max_cases: 50, max_leads_per_month: 30,
    has_marketplace: false, has_priority_support: false, has_api_access: false,
    has_custom_domain: false, has_white_label: false, has_analytics: false,
    trial_days: 0, grace_period_days: 3,
    is_active: true, is_public: true, is_recommended: false, sort_order: 0,
  };
}

function openPlanModal(plan) {
  editPlan.value = plan;
  planForm.value = plan ? { ...plan } : defaultPlanForm();
  showPlanModal.value = true;
}

async function savePlan() {
  planSaving.value = true;
  try {
    if (editPlan.value) {
      await api.patch(`/owner/billing/plans/${editPlan.value.slug}`, planForm.value);
    } else {
      await api.post('/owner/billing/plans', planForm.value);
    }
    showPlanModal.value = false;
    await loadPlans();
  } catch (e) { alert(e.response?.data?.message || t('owner.billing.error')); }
  planSaving.value = false;
}

async function deactivatePlan(slug) {
  try {
    await api.delete(`/owner/billing/plans/${slug}`);
    await loadPlans();
  } catch (e) { alert(e.response?.data?.message || t('owner.billing.error')); }
}

async function activatePlan(slug) {
  try {
    await api.patch(`/owner/billing/plans/${slug}`, { is_active: true });
    await loadPlans();
  } catch (e) { alert(e.response?.data?.message || t('owner.billing.error')); }
}

function planFeatures(p) {
  const features = [];
  if (p.has_marketplace)        features.push({ label: t('owner.billing.featureMarketplace'),   class: 'bg-green-50 text-green-700' });
  if (p.has_analytics)          features.push({ label: t('owner.billing.featureAnalytics'),     class: 'bg-purple-50 text-purple-700' });
  if (p.has_api_access)         features.push({ label: t('owner.billing.featureApi'),           class: 'bg-orange-50 text-orange-700' });
  if (p.has_white_label)        features.push({ label: t('owner.billing.featureWhiteLabel'),   class: 'bg-indigo-50 text-indigo-700' });
  if (p.has_custom_domain)      features.push({ label: t('owner.billing.featureCustomDomain'), class: 'bg-blue-50 text-blue-700' });
  if (p.has_priority_support)   features.push({ label: t('owner.billing.featureVipSupport'),   class: 'bg-yellow-50 text-yellow-700' });
  return features;
}

function planDisplayName(slug) {
  const map = {
    micro: 'Micro', trial: 'Trial', starter: 'Starter',
    pro: 'Professional', business: 'Business', enterprise: 'Enterprise', franchise: 'Franchise',
  };
  return map[slug] || slug;
}

// ======================== SETTINGS ========================
const settings = ref([]);
const settingsMap = reactive({});
const settingsLoading = ref(false);
const settingsSaving = ref(false);
const settingsSaved = ref(false);

async function loadSettings() {
  settingsLoading.value = true;
  try {
    const { data } = await api.get('/owner/billing/settings');
    settings.value = data.data;
    for (const s of data.data) {
      settingsMap[s.key] = s.type === 'boolean' ? !!s.value : s.value;
    }
  } catch { /* silent */ }
  settingsLoading.value = false;
}

function getSettingValue(key) {
  return settingsMap[key];
}

function toggleSetting(key) {
  settingsMap[key] = !settingsMap[key];
}

async function saveSettings() {
  settingsSaving.value = true;
  settingsSaved.value = false;
  try {
    const payload = Object.keys(settingsMap).map(key => ({
      key,
      value: settingsMap[key],
    }));
    await api.patch('/owner/billing/settings', { settings: payload });
    settingsSaved.value = true;
    setTimeout(() => { settingsSaved.value = false; }, 3000);
  } catch (e) { alert(t('owner.billing.saveError')); }
  settingsSaving.value = false;
}

// ======================== COUPONS ========================
const coupons = ref([]);
const couponsLoading = ref(false);
const showCouponModal = ref(false);
const editCoupon = ref(null);
const couponSaving = ref(false);
const couponForm = ref({});

async function loadCoupons() {
  couponsLoading.value = true;
  try {
    const { data } = await api.get('/owner/billing/coupons');
    coupons.value = data.data;
  } catch { /* silent */ }
  couponsLoading.value = false;
}

function openCouponModal(coupon) {
  editCoupon.value = coupon;
  couponForm.value = coupon ? { ...coupon } : {
    code: '', description: '', discount_type: 'percentage',
    discount_value: 10, max_uses: 0, valid_until: '', is_active: true,
  };
  showCouponModal.value = true;
}

async function saveCoupon() {
  couponSaving.value = true;
  try {
    if (editCoupon.value) {
      await api.patch(`/owner/billing/coupons/${editCoupon.value.id}`, couponForm.value);
    } else {
      await api.post('/owner/billing/coupons', couponForm.value);
    }
    showCouponModal.value = false;
    await loadCoupons();
  } catch (e) { alert(e.response?.data?.message || t('owner.billing.error')); }
  couponSaving.value = false;
}

// ======================== HELPERS ========================
function fmtMoney(val) {
  if (!val && val !== 0) return '0 ' + t('owner.billing.currency');
  return Number(val).toLocaleString('ru-RU') + ' ' + t('owner.billing.currency');
}

function formatDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
}

function formatDateShort(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
}

function statusDotColor(s) {
  const map = { active: 'bg-green-500', trialing: 'bg-blue-500', past_due: 'bg-orange-500', cancelled: 'bg-gray-400', expired: 'bg-red-500' };
  return map[s] || 'bg-gray-400';
}

function statusLabel(s) {
  const map = {
    active: t('owner.billing.statusActive'),
    trialing: t('owner.billing.statusTrialing'),
    past_due: t('owner.billing.statusPastDue'),
    cancelled: t('owner.billing.statusCancelled'),
    expired: t('owner.billing.statusExpired'),
  };
  return map[s] || s;
}

function eventLabel(ev) {
  const map = {
    'subscription.created': t('owner.billing.eventSubCreated'),
    'subscription.renewed': t('owner.billing.eventSubRenewed'),
    'subscription.cancelled': t('owner.billing.eventSubCancelled'),
    'subscription.expired': t('owner.billing.eventSubExpired'),
    'activation_fee.paid': t('owner.billing.eventActivationPaid'),
    'earn_first.deducted': t('owner.billing.eventEarnDeducted'),
    'payment.succeeded': t('owner.billing.eventPaymentOk'),
    'payment.failed': t('owner.billing.eventPaymentFail'),
    'payout.completed': t('owner.billing.eventPayout'),
    'invoice.created': t('owner.billing.eventInvoiceCreated'),
    'invoice.overdue': t('owner.billing.eventInvoiceOverdue'),
  };
  return map[ev] || ev;
}

function eventIcon(ev) {
  const map = {
    'subscription.created': '+',
    'subscription.renewed': '~',
    'subscription.cancelled': 'x',
    'activation_fee.paid': '$',
    'earn_first.deducted': '%',
    'payment.succeeded': '$',
    'payment.failed': '!',
    'payout.completed': '>',
  };
  return map[ev] || '?';
}

function eventIconBg(ev) {
  if (ev.includes('paid') || ev.includes('succeeded') || ev.includes('created') || ev.includes('renewed')) return 'bg-green-50 text-green-600';
  if (ev.includes('deducted')) return 'bg-blue-50 text-blue-600';
  if (ev.includes('cancelled') || ev.includes('expired') || ev.includes('failed') || ev.includes('overdue')) return 'bg-red-50 text-red-600';
  return 'bg-gray-50 text-gray-500';
}

onMounted(() => {
  loadDashboard();
  loadPlans();
  loadSettings();
  loadCoupons();
});
</script>
