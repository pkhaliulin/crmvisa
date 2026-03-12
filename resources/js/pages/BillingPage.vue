<template>
  <div class="max-w-4xl mx-auto space-y-4">
    <div>
      <h1 class="text-xl font-bold text-gray-900">{{ t('crm.billing.title') }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ t('crm.billing.subtitle') }}</p>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Toast -->
      <Teleport to="body">
        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-y-2"
          leave-active-class="transition duration-200 ease-in" leave-to-class="opacity-0 translate-y-2">
          <div v-if="toast"
            :class="['fixed top-5 right-5 z-50 text-white text-sm px-4 py-3 rounded-xl shadow-lg max-w-xs', toastError ? 'bg-red-600' : 'bg-[#0A1F44]']">
            {{ toast }}
          </div>
        </Transition>
      </Teleport>

      <!-- Текущий план -->
      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-8 h-8 bg-[#1BA97F]/10 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-[#1BA97F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
          </div>
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">{{ t('crm.billing.currentSub') }}</h2>
        </div>

        <div class="flex flex-wrap items-start gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
              <span class="text-2xl font-bold text-[#0A1F44]">{{ currentPlanName }}</span>
              <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', statusBadgeClass(sub.status)]">
                {{ statusLabel(sub.status) }}
              </span>
            </div>
            <div v-if="sub.plan?.slug" class="text-xs text-gray-400 mt-1">{{ t('crm.billing.plan') }} {{ sub.plan_slug }}</div>
            <div v-if="sub.expires_at" class="mt-2 text-sm text-gray-500">
              {{ t('crm.billing.validUntil') }} <span class="font-medium text-gray-700">{{ formatDate(sub.expires_at) }}</span>
              <span v-if="sub.days_left !== null && sub.days_left !== undefined" class="ml-1 text-xs"
                :class="sub.days_left <= 7 ? 'text-red-500 font-bold' : sub.days_left <= 30 ? 'text-amber-500' : 'text-gray-400'">
                ({{ sub.days_left }} {{ t('crm.billing.daysLeft', { n: sub.days_left }).replace(/[()]/g, '') }})
              </span>
            </div>
            <div v-else class="mt-2 text-sm text-gray-400">{{ t('crm.billing.unlimited') }}</div>

            <div v-if="sub.payment_model" class="mt-2 text-xs text-gray-400">
              <span v-if="sub.payment_model === 'earn_first'" class="text-green-600 font-medium">{{ t('crm.billing.earnFirst') }}</span>
              <span v-else-if="sub.payment_model === 'prepaid'" class="text-blue-600 font-medium">{{ t('crm.billing.prepaid') }}</span>
              <span v-else class="text-gray-600 font-medium">{{ sub.payment_model }}</span>
            </div>

            <div v-if="sub.earn_first_progress" class="mt-3 p-3 bg-green-50 rounded-lg">
              <div class="flex justify-between text-xs mb-1">
                <span class="text-green-700 font-medium">{{ t('crm.billing.autoDebitProgress') }}</span>
                <span class="text-green-700 font-bold">{{ sub.earn_first_progress.pct }}%</span>
              </div>
              <div class="h-2 bg-green-100 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: sub.earn_first_progress.pct + '%' }"></div>
              </div>
              <div class="text-[10px] text-green-600 mt-1">
                {{ t('crm.billing.amountOf', { used: fmtMoney(sub.earn_first_progress.deducted), total: fmtMoney(sub.earn_first_progress.target) }) }}
              </div>
            </div>

            <div v-if="sub.is_in_grace_period" class="mt-2 px-3 py-2 bg-amber-50 rounded-lg border border-amber-200">
              <div class="text-xs font-medium text-amber-700">{{ t('crm.billing.gracePeriod', { date: formatDate(sub.grace_ends_at) }) }}</div>
            </div>
          </div>

          <div class="text-right shrink-0">
            <p class="text-3xl font-bold text-[#1BA97F]">{{ currentPlanPrice }}</p>
            <p v-if="currentPlanPrice !== t('crm.billing.free')" class="text-xs text-gray-400 mt-0.5">{{ t('crm.billing.perMonth') }}</p>
          </div>
        </div>
      </section>

      <!-- Запланированный downgrade -->
      <section v-if="sub.pending_downgrade" class="bg-amber-50 rounded-xl border border-amber-200 p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <h3 class="font-semibold text-amber-800">{{ t('crm.billing.pendingDowngrade') }}</h3>
            </div>
            <p class="text-sm text-amber-700">
              {{ t('crm.billing.downgradeInfo', { plan: sub.pending_downgrade.plan_name }) }}
              ({{ sub.pending_downgrade.billing_period === 'yearly' ? t('crm.billing.yearly') : t('crm.billing.monthly') }})
              {{ t('crm.billing.downgradeDate', { date: formatDate(sub.pending_downgrade.change_at) }) }}
              {{ t('crm.billing.downgradeKeepFull') }}
            </p>
          </div>
          <button @click="cancelDowngrade"
            class="shrink-0 px-3 py-1.5 text-xs font-medium text-amber-700 bg-white border border-amber-300 rounded-lg hover:bg-amber-100 transition-colors">
            {{ t('crm.billing.cancelDowngrade') }}
          </button>
        </div>
      </section>

      <!-- Лимиты -->
      <section v-if="limits" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide mb-4">{{ t('crm.billing.limitsTitle') }}</h2>
        <div class="space-y-4">
          <div>
            <div class="flex justify-between text-sm mb-1.5">
              <span class="text-gray-600">{{ t('crm.billing.managers') }}</span>
              <span class="font-medium text-gray-900">
                {{ limits.managers_count }}
                <span class="text-gray-400 font-normal">/ {{ limits.max_managers || t('crm.billing.unlimitedLabel') }}</span>
              </span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :class="managersPct >= 90 ? 'bg-red-500' : managersPct >= 70 ? 'bg-yellow-400' : 'bg-[#1BA97F]'"
                :style="{ width: (limits.max_managers ? managersPct : 20) + '%' }"></div>
            </div>
          </div>

          <div>
            <div class="flex justify-between text-sm mb-1.5">
              <span class="text-gray-600">{{ t('crm.billing.activeCases') }}</span>
              <span class="font-medium text-gray-900">
                {{ limits.cases_count }}
                <span class="text-gray-400 font-normal">/ {{ limits.max_cases || t('crm.billing.unlimitedLabel') }}</span>
              </span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :class="casesPct >= 90 ? 'bg-red-500' : casesPct >= 70 ? 'bg-yellow-400' : 'bg-[#1BA97F]'"
                :style="{ width: (limits.max_cases ? casesPct : 20) + '%' }"></div>
            </div>
          </div>

          <div v-if="limits.max_leads_per_month">
            <div class="flex justify-between text-sm mb-1.5">
              <span class="text-gray-600">{{ t('crm.billing.leadsMonth') }}</span>
              <span class="font-medium text-gray-900">
                {{ limits.leads_this_month || 0 }}
                <span class="text-gray-400 font-normal">/ {{ limits.max_leads_per_month }}</span>
              </span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :class="leadsPct >= 90 ? 'bg-red-500' : leadsPct >= 70 ? 'bg-yellow-400' : 'bg-[#1BA97F]'"
                :style="{ width: leadsPct + '%' }"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- Тарифные планы -->
      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">{{ t('crm.billing.plansTitle') }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ t('crm.billing.plansSubtitle') }}</p>
          </div>
          <div class="flex bg-gray-100 rounded-lg p-0.5">
            <button @click="selectedPeriod = 'monthly'"
              :class="['px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
                selectedPeriod === 'monthly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
              {{ t('crm.billing.periodMonth') }}
            </button>
            <button @click="selectedPeriod = 'yearly'"
              :class="['px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
                selectedPeriod === 'yearly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
              {{ t('crm.billing.periodYear') }} <span class="text-[#1BA97F] font-bold">{{ t('crm.billing.periodDiscount') }}</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="plan in availablePlans" :key="plan.slug"
            :class="[
              'relative flex flex-col rounded-xl border-2 p-4 transition-all',
              isCurrentPlan(plan.slug)
                ? 'border-[#1BA97F] bg-[#1BA97F]/5 ring-1 ring-[#1BA97F]/20'
                : plan.is_recommended
                  ? 'border-blue-400 bg-blue-50/30 hover:border-blue-500'
                  : 'border-gray-200 bg-white hover:border-gray-300',
            ]">
            <div v-if="isCurrentPlan(plan.slug)"
              class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#1BA97F] text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider whitespace-nowrap">
              {{ t('crm.billing.currentPlan') }}
            </div>
            <div v-else-if="plan.is_recommended"
              class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-500 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full whitespace-nowrap">
              {{ t('crm.billing.recommended') }}
            </div>

            <p class="font-bold text-[#0A1F44] text-base">{{ plan.name }}</p>
            <p class="text-2xl font-extrabold mt-1" :class="isCurrentPlan(plan.slug) ? 'text-[#1BA97F]' : 'text-gray-800'">
              {{ planPrice(plan) > 0 ? fmtMoney(planPrice(plan)) : t('crm.billing.free') }}
            </p>
            <p v-if="planPrice(plan) > 0" class="text-[11px] text-gray-400 -mt-0.5">
              / {{ selectedPeriod === 'yearly' ? t('crm.billing.periodYear').replace(/ .*/, '') : t('crm.billing.periodMonth').toLowerCase() }}
            </p>

            <ul class="mt-3 space-y-1.5 flex-1">
              <li class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                {{ plan.max_managers === 0 ? t('crm.billing.unlimitedManagers') : t('crm.billing.upToManagers', { n: plan.max_managers }) }}
              </li>
              <li class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                {{ plan.max_cases === 0 ? t('crm.billing.unlimitedCases') : t('crm.billing.upToCases', { n: plan.max_cases }) }}
              </li>
              <li v-if="plan.has_marketplace" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                {{ t('crm.billing.marketplace') }}
              </li>
              <li v-if="plan.has_analytics" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                {{ t('crm.billing.analytics') }}
              </li>
              <li v-if="plan.has_api_access" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                {{ t('crm.billing.apiAccess') }}
              </li>
              <li v-if="plan.has_white_label" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                {{ t('crm.billing.whiteLabel') }}
              </li>
              <li v-if="plan.has_priority_support" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                {{ t('crm.billing.prioritySupport') }}
              </li>
            </ul>

            <button @click="openPlanDetail(plan)"
              class="mt-4 w-full py-1.5 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
              {{ t('crm.billing.details') }}
            </button>
            <button @click="openChangePlan(plan)"
              :disabled="isCurrentPlan(plan.slug) || changingPlan"
              :class="[
                'mt-1.5 w-full py-2 rounded-lg text-sm font-medium transition-colors',
                isCurrentPlan(plan.slug)
                  ? 'bg-[#1BA97F]/10 text-[#1BA97F] cursor-default'
                  : isUpgrade(plan)
                    ? 'bg-[#1BA97F] text-white hover:bg-[#158a68]'
                    : 'bg-[#0A1F44] text-white hover:bg-[#0d2a5e]',
              ]">
              {{ isCurrentPlan(plan.slug) ? t('crm.billing.yourPlan') : isUpgrade(plan) ? t('crm.billing.switchPlan') : t('crm.billing.selectPlan') }}
            </button>
          </div>
        </div>
      </section>

      <!-- Способ оплаты -->
      <section v-if="showPaymentSection" class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-8 h-8 bg-blue-500/10 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
          </div>
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">{{ t('crm.billing.paymentMethod') }}</h2>
        </div>

        <p class="text-sm text-gray-500 mb-4">{{ t('crm.billing.selectPaymentMethod') }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
          <button
            v-for="pm in paymentMethods"
            :key="pm.value"
            @click="selectedPaymentMethod = pm.value"
            :class="[
              'flex items-center gap-3 p-4 rounded-xl border-2 transition-all text-left',
              selectedPaymentMethod === pm.value
                ? 'border-[#1BA97F] bg-[#1BA97F]/5 ring-1 ring-[#1BA97F]/20'
                : 'border-gray-200 hover:border-gray-300 bg-white',
            ]"
          >
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
              :class="pm.bgClass">
              <img v-if="pm.logo" :src="pm.logo" :alt="pm.label" class="h-5 object-contain">
              <span v-else class="text-sm font-bold" :class="pm.textClass">{{ pm.shortName }}</span>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">{{ pm.label }}</p>
              <p class="text-xs text-gray-400">{{ pm.desc }}</p>
            </div>
            <div v-if="selectedPaymentMethod === pm.value" class="ml-auto shrink-0">
              <svg class="w-5 h-5 text-[#1BA97F]" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
            </div>
          </button>
        </div>

        <div v-if="selectedPaymentMethod" class="flex items-center gap-3">
          <button
            @click="initiatePayment"
            :disabled="paymentLoading"
            class="px-6 py-2.5 bg-[#1BA97F] text-white text-sm font-medium rounded-lg hover:bg-[#158a68] transition-colors flex items-center gap-2 disabled:opacity-60 disabled:cursor-wait"
          >
            <div v-if="paymentLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            {{ paymentButtonLabel }}
          </button>
          <span class="text-sm text-gray-500">
            {{ t('crm.billing.amount') }}: <span class="font-bold text-gray-900">{{ paymentAmountFormatted }}</span>
          </span>
        </div>

        <!-- Статус последнего платежа -->
        <div v-if="lastPaymentStatus" class="mt-4 p-3 rounded-lg" :class="lastPaymentStatusClass">
          <p class="text-sm font-medium">{{ lastPaymentStatusText }}</p>
        </div>
      </section>

      <!-- История -->
      <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">{{ t('crm.billing.paymentHistory') }}</h2>
        </div>
        <div v-if="transactions.length === 0" class="px-6 py-10 text-center text-gray-400 text-sm">
          {{ t('crm.billing.paymentEmpty') }}
        </div>
        <table v-else class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-left">
              <th class="px-6 py-3 font-medium text-gray-500">{{ t('crm.billing.colDate') }}</th>
              <th class="px-6 py-3 font-medium text-gray-500">{{ t('crm.billing.colType') }}</th>
              <th class="px-6 py-3 font-medium text-gray-500">{{ t('crm.billing.colAmount') }}</th>
              <th class="px-6 py-3 font-medium text-gray-500">{{ t('crm.billing.colStatus') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tx in transactions" :key="tx.id"
              class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
              <td class="px-6 py-3 text-gray-700">{{ formatDate(tx.created_at || tx.paid_at) }}</td>
              <td class="px-6 py-3 font-medium text-gray-900">{{ txTypeLabel(tx.type || tx.plan) }}</td>
              <td class="px-6 py-3 text-gray-700">{{ tx.amount ? fmtMoney(tx.amount) : '--' }}</td>
              <td class="px-6 py-3">
                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', txStatusClass(tx.status)]">
                  {{ txStatusLabel(tx.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- Модалка подтверждения смены тарифа -->
      <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
          leave-active-class="transition duration-150 ease-in" leave-to-class="opacity-0">
          <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showConfirmModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
              <h3 class="text-lg font-bold text-[#0A1F44]">{{ t('crm.billing.changePlanTitle') }}</h3>

              <div class="mt-4 p-4 bg-gray-50 rounded-xl space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">{{ t('crm.billing.currentPlanLabel') }}</span>
                  <span class="font-medium text-gray-700">{{ currentPlanName }}</span>
                </div>
                <div class="flex items-center justify-center">
                  <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                  </svg>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">{{ t('crm.billing.newPlanLabel') }}</span>
                  <span class="font-bold" :class="isUpgrade(selectedPlan) ? 'text-[#1BA97F]' : 'text-[#0A1F44]'">
                    {{ selectedPlan?.name }}
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">{{ t('crm.billing.costLabel') }}</span>
                  <span class="font-bold text-gray-900">
                    {{ selectedPlan ? (planPrice(selectedPlan) > 0 ? fmtMoney(planPrice(selectedPlan)) : t('crm.billing.free')) : '' }}
                    <span v-if="selectedPlan && planPrice(selectedPlan) > 0" class="text-gray-400 font-normal text-xs">
                      / {{ selectedPeriod === 'yearly' ? t('crm.billing.periodYear').replace(/ .*/, '') : t('crm.billing.periodMonth').toLowerCase() }}
                    </span>
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">{{ t('crm.billing.periodLabel') }}</span>
                  <span class="font-medium text-gray-700">{{ selectedPeriod === 'yearly' ? t('crm.billing.yearlyLabel') : t('crm.billing.monthlyLabel') }}</span>
                </div>
              </div>

              <div v-if="isUpgrade(selectedPlan)" class="mt-3 p-3 bg-green-50 rounded-lg space-y-1">
                <p class="text-xs text-green-700 font-medium">{{ t('crm.billing.upgradeNote') }}</p>
                <p class="text-xs text-green-600">{{ t('crm.billing.upgradeCredit') }}</p>
              </div>
              <div v-else class="mt-3 p-3 bg-amber-50 rounded-lg space-y-1">
                <p class="text-xs text-amber-700 font-medium">{{ t('crm.billing.downgradeNote') }}</p>
                <p v-if="sub.expires_at" class="text-xs text-amber-600">
                  {{ t('crm.billing.downgradeKeep', { date: formatDate(sub.expires_at) }) }}
                </p>
              </div>

              <div class="mt-5 flex gap-3">
                <button @click="showConfirmModal = false" :disabled="changingPlan"
                  class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                  {{ t('crm.billing.cancel') }}
                </button>
                <button @click="confirmChangePlan" :disabled="changingPlan"
                  :class="[
                    'flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition-colors flex items-center justify-center gap-2',
                    isUpgrade(selectedPlan)
                      ? 'bg-[#1BA97F] hover:bg-[#158a68]'
                      : 'bg-[#0A1F44] hover:bg-[#0d2a5e]',
                    changingPlan ? 'opacity-70 cursor-wait' : '',
                  ]">
                  <div v-if="changingPlan" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  {{ changingPlan ? t('crm.billing.applying') : t('crm.billing.confirm') }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
      <!-- Модалка подробнее о тарифе -->
      <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
          leave-active-class="transition duration-150 ease-in" leave-to-class="opacity-0">
          <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showDetailModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[85vh] overflow-y-auto">
              <div class="sticky top-0 bg-white px-6 pt-6 pb-3 border-b border-gray-100 flex items-start justify-between">
                <div>
                  <h3 class="text-lg font-bold text-[#0A1F44]">{{ detailPlan?.name }}</h3>
                  <p class="text-sm text-gray-500 mt-0.5">{{ detailPlan ? (planPrice(detailPlan) > 0 ? fmtMoney(planPrice(detailPlan)) + ' / ' + (selectedPeriod === 'yearly' ? t('crm.billing.periodYear').replace(/ .*/, '') : t('crm.billing.periodMonth').toLowerCase()) : t('crm.billing.free')) : '' }}</p>
                </div>
                <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600 p-1">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
              <div class="px-6 py-5 space-y-5" v-if="detailPlan">
                <!-- Для кого -->
                <div>
                  <h4 class="text-sm font-semibold text-[#0A1F44] mb-2">{{ t('crm.billing.whoFits') }}</h4>
                  <p class="text-sm text-gray-600 leading-relaxed">{{ planInfo[detailPlan.slug]?.audience }}</p>
                </div>

                <!-- Преимущества -->
                <div>
                  <h4 class="text-sm font-semibold text-[#0A1F44] mb-2">{{ t('crm.billing.advantages') }}</h4>
                  <ul class="space-y-2">
                    <li v-for="(adv, i) in planInfo[detailPlan.slug]?.advantages" :key="i" class="flex items-start gap-2 text-sm text-gray-600">
                      <span class="text-[#1BA97F] mt-0.5 shrink-0 font-bold">+</span>
                      <span>{{ adv }}</span>
                    </li>
                  </ul>
                </div>

                <!-- Что входит -->
                <div>
                  <h4 class="text-sm font-semibold text-[#0A1F44] mb-2">{{ t('crm.billing.includedTitle') }}</h4>
                  <div class="grid grid-cols-2 gap-2">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                      <span class="w-1.5 h-1.5 bg-[#1BA97F] rounded-full shrink-0"></span>
                      {{ detailPlan.max_managers === 0 ? t('crm.billing.unlimitedManagers') : t('crm.billing.upToManagers', { n: detailPlan.max_managers }) }}
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                      <span class="w-1.5 h-1.5 bg-[#1BA97F] rounded-full shrink-0"></span>
                      {{ detailPlan.max_cases === 0 ? t('crm.billing.unlimitedCases') : t('crm.billing.upToCases', { n: detailPlan.max_cases }) }}
                    </div>
                    <div v-if="detailPlan.max_leads_per_month" class="flex items-center gap-2 text-sm text-gray-600">
                      <span class="w-1.5 h-1.5 bg-[#1BA97F] rounded-full shrink-0"></span>
                      {{ t('crm.billing.leadsPerMonth', { n: detailPlan.max_leads_per_month }) }}
                    </div>
                    <div v-else class="flex items-center gap-2 text-sm text-gray-600">
                      <span class="w-1.5 h-1.5 bg-[#1BA97F] rounded-full shrink-0"></span>
                      {{ t('crm.billing.unlimitedLeads') }}
                    </div>
                    <div v-if="detailPlan.has_marketplace" class="flex items-center gap-2 text-sm text-gray-600">
                      <span class="w-1.5 h-1.5 bg-[#1BA97F] rounded-full shrink-0"></span>
                      {{ t('crm.billing.marketplace') }}
                    </div>
                    <div v-if="detailPlan.has_analytics" class="flex items-center gap-2 text-sm text-gray-600">
                      <span class="w-1.5 h-1.5 bg-[#1BA97F] rounded-full shrink-0"></span>
                      {{ t('crm.billing.analyticsReports') }}
                    </div>
                    <div v-if="detailPlan.has_api_access" class="flex items-center gap-2 text-sm text-gray-600">
                      <span class="w-1.5 h-1.5 bg-[#1BA97F] rounded-full shrink-0"></span>
                      {{ t('crm.billing.apiAccess') }}
                    </div>
                    <div v-if="detailPlan.has_white_label" class="flex items-center gap-2 text-sm text-gray-600">
                      <span class="w-1.5 h-1.5 bg-[#1BA97F] rounded-full shrink-0"></span>
                      {{ t('crm.billing.whiteLabel') }}
                    </div>
                    <div v-if="detailPlan.has_custom_domain" class="flex items-center gap-2 text-sm text-gray-600">
                      <span class="w-1.5 h-1.5 bg-[#1BA97F] rounded-full shrink-0"></span>
                      {{ t('crm.billing.customDomain') }}
                    </div>
                    <div v-if="detailPlan.has_priority_support" class="flex items-center gap-2 text-sm text-gray-600">
                      <span class="w-1.5 h-1.5 bg-[#1BA97F] rounded-full shrink-0"></span>
                      {{ t('crm.billing.prioritySupport') }}
                    </div>
                  </div>
                </div>

                <!-- Собственные клиенты -->
                <div class="p-4 bg-blue-50 rounded-xl">
                  <h4 class="text-sm font-semibold text-blue-800 mb-1.5">{{ t('crm.billing.ownClients') }}</h4>
                  <p class="text-xs text-blue-700 leading-relaxed">{{ t('crm.billing.ownClientsDesc') }}</p>
                </div>

                <!-- API блок для Enterprise -->
                <div v-if="detailPlan.has_api_access" class="p-4 bg-green-50 rounded-xl">
                  <h4 class="text-sm font-semibold text-green-800 mb-1.5">{{ t('crm.billing.socialApi') }}</h4>
                  <p class="text-xs text-green-700 leading-relaxed">{{ t('crm.billing.socialApiDesc') }}</p>
                </div>

                <!-- Earn-first -->
                <div v-if="detailPlan.earn_first_enabled" class="p-4 bg-amber-50 rounded-xl">
                  <h4 class="text-sm font-semibold text-amber-800 mb-1.5">{{ t('crm.billing.earnFirstModel') }}</h4>
                  <p class="text-xs text-amber-700 leading-relaxed">{{ t('crm.billing.earnFirstDesc', { pct: detailPlan.earn_first_deduction_pct }) }}</p>
                </div>
              </div>

              <div class="px-6 pb-6 pt-2 flex gap-3">
                <button @click="showDetailModal = false"
                  class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                  {{ t('crm.billing.close') }}
                </button>
                <button v-if="!isCurrentPlan(detailPlan?.slug)" @click="showDetailModal = false; openChangePlan(detailPlan)"
                  :class="[
                    'flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition-colors',
                    isUpgrade(detailPlan)
                      ? 'bg-[#1BA97F] hover:bg-[#158a68]'
                      : 'bg-[#0A1F44] hover:bg-[#0d2a5e]',
                  ]">
                  {{ isUpgrade(detailPlan) ? t('crm.billing.switchToThis') : t('crm.billing.selectThis') }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import { formatDate } from '@/utils/format';

const { t } = useI18n();

const loading = ref(true);
const toast = ref('');
const toastError = ref(false);

const sub = ref({
  plan_slug: 'trial',
  plan: null,
  status: 'active',
  expires_at: null,
  days_left: null,
  payment_model: null,
  earn_first_progress: null,
  is_in_grace_period: false,
  grace_ends_at: null,
  pending_downgrade: null,
});

const limits = ref(null);
const transactions = ref([]);
const availablePlans = ref([]);
const selectedPeriod = ref('monthly');
const showConfirmModal = ref(false);
const selectedPlan = ref(null);
const changingPlan = ref(false);
const showDetailModal = ref(false);
const detailPlan = ref(null);
const selectedPaymentMethod = ref(null);
const paymentLoading = ref(false);
const lastPaymentStatus = ref(null); // null | 'pending' | 'completed' | 'failed'

const planInfo = computed(() => ({
  trial: {
    audience: t('crm.billing.planInfoTrial.audience'),
    advantages: [
      t('crm.billing.planInfoTrial.adv1'),
      t('crm.billing.planInfoTrial.adv2'),
      t('crm.billing.planInfoTrial.adv3'),
      t('crm.billing.planInfoTrial.adv4'),
      t('crm.billing.planInfoTrial.adv5'),
    ],
  },
  starter: {
    audience: t('crm.billing.planInfoStarter.audience'),
    advantages: [
      t('crm.billing.planInfoStarter.adv1'),
      t('crm.billing.planInfoStarter.adv2'),
      t('crm.billing.planInfoStarter.adv3'),
      t('crm.billing.planInfoStarter.adv4'),
      t('crm.billing.planInfoStarter.adv5'),
      t('crm.billing.planInfoStarter.adv6'),
    ],
  },
  pro: {
    audience: t('crm.billing.planInfoPro.audience'),
    advantages: [
      t('crm.billing.planInfoPro.adv1'),
      t('crm.billing.planInfoPro.adv2'),
      t('crm.billing.planInfoPro.adv3'),
      t('crm.billing.planInfoPro.adv4'),
      t('crm.billing.planInfoPro.adv5'),
      t('crm.billing.planInfoPro.adv6'),
      t('crm.billing.planInfoPro.adv7'),
    ],
  },
  enterprise: {
    audience: t('crm.billing.planInfoEnterprise.audience'),
    advantages: [
      t('crm.billing.planInfoEnterprise.adv1'),
      t('crm.billing.planInfoEnterprise.adv2'),
      t('crm.billing.planInfoEnterprise.adv3'),
      t('crm.billing.planInfoEnterprise.adv4'),
      t('crm.billing.planInfoEnterprise.adv5'),
      t('crm.billing.planInfoEnterprise.adv6'),
      t('crm.billing.planInfoEnterprise.adv7'),
      t('crm.billing.planInfoEnterprise.adv8'),
      t('crm.billing.planInfoEnterprise.adv9'),
    ],
  },
}));

const paymentMethods = computed(() => [
  {
    value: 'click',
    label: 'Click',
    shortName: 'Click',
    desc: t('crm.billing.payViaClick'),
    bgClass: 'bg-blue-100',
    textClass: 'text-blue-700',
    logo: null,
  },
  {
    value: 'payme',
    label: 'Payme',
    shortName: 'Payme',
    desc: t('crm.billing.payViaPayme'),
    bgClass: 'bg-cyan-100',
    textClass: 'text-cyan-700',
    logo: null,
  },
  {
    value: 'uzum',
    label: 'Uzum',
    shortName: 'Uzum',
    desc: t('crm.billing.payViaUzum'),
    bgClass: 'bg-purple-100',
    textClass: 'text-purple-700',
    logo: null,
  },
]);

const showPaymentSection = computed(() => {
  // Показывать секцию оплаты если есть подписка с ценой > 0
  if (!sub.value.plan_slug || sub.value.plan_slug === 'trial') return false;
  const plan = availablePlans.value.find(p => p.slug === sub.value.plan_slug);
  return plan && (plan.price_uzs > 0 || plan.price_yearly > 0);
});

const paymentAmountFormatted = computed(() => {
  if (!sub.value.plan) return fmtMoney(0);
  const plan = sub.value.plan;
  const price = sub.value.billing_period === 'yearly' ? (plan.price_yearly || plan.price_uzs) : plan.price_uzs;
  return fmtMoney(price || 0);
});

const paymentButtonLabel = computed(() => {
  const methodLabels = {
    click: t('crm.billing.payViaClick'),
    payme: t('crm.billing.payViaPayme'),
    uzum: t('crm.billing.payViaUzum'),
  };
  return methodLabels[selectedPaymentMethod.value] || t('crm.billing.selectPaymentMethod');
});

const lastPaymentStatusClass = computed(() => {
  if (lastPaymentStatus.value === 'completed') return 'bg-green-50 text-green-700';
  if (lastPaymentStatus.value === 'failed') return 'bg-red-50 text-red-700';
  return 'bg-yellow-50 text-yellow-700';
});

const lastPaymentStatusText = computed(() => {
  if (lastPaymentStatus.value === 'completed') return t('crm.billing.paymentCompleted');
  if (lastPaymentStatus.value === 'failed') return t('crm.billing.paymentFailed');
  return t('crm.billing.paymentPending');
});

async function initiatePayment() {
  if (!selectedPaymentMethod.value || paymentLoading.value) return;
  paymentLoading.value = true;
  lastPaymentStatus.value = null;

  try {
    const res = await api.post('/payments/create', {
      provider: selectedPaymentMethod.value,
      type: 'subscription',
    });

    const data = res.data?.data ?? res.data;

    if (data.checkout_url) {
      lastPaymentStatus.value = 'pending';
      // Редирект на страницу оплаты
      window.location.href = data.checkout_url;
    } else {
      showToast(t('crm.billing.paymentFailed'), true);
      lastPaymentStatus.value = 'failed';
    }
  } catch (err) {
    const msg = err.response?.data?.message || t('crm.billing.paymentFailed');
    showToast(msg, true);
    lastPaymentStatus.value = 'failed';
  } finally {
    paymentLoading.value = false;
  }
}

function openPlanDetail(plan) {
  detailPlan.value = plan;
  showDetailModal.value = true;
}

const currentPlanName = computed(() => {
  if (sub.value.plan?.name) return sub.value.plan.name;
  const map = { trial: 'Trial', starter: 'Starter', pro: 'Professional', enterprise: 'Enterprise', micro: 'Micro', business: 'Business', franchise: 'Franchise' };
  return map[sub.value.plan_slug] || sub.value.plan_slug || 'N/A';
});

const currentPlanPrice = computed(() => {
  if (sub.value.plan?.price_uzs > 0) return fmtMoney(sub.value.plan.price_uzs);
  const plan = availablePlans.value.find(p => p.slug === sub.value.plan_slug);
  if (plan?.price_uzs > 0) return fmtMoney(plan.price_uzs);
  return t('crm.billing.free');
});

function planPrice(plan) {
  if (!plan) return 0;
  return selectedPeriod.value === 'yearly' ? (plan.price_yearly || 0) : (plan.price_uzs || 0);
}

function isCurrentPlan(slug) {
  return sub.value.plan_slug === slug;
}

function isUpgrade(plan) {
  if (!plan) return false;
  const currentPlan = availablePlans.value.find(p => p.slug === sub.value.plan_slug);
  if (!currentPlan) return true;
  return (plan.price_uzs || 0) > (currentPlan.price_uzs || 0);
}

function statusLabel(s) {
  const map = {
    active: t('crm.billing.statusActive'),
    expired: t('crm.billing.statusExpired'),
    cancelled: t('crm.billing.statusCancelled'),
    trialing: t('crm.billing.statusTrial'),
    past_due: t('crm.billing.statusPastDue'),
  };
  return map[s] || s || 'N/A';
}

function statusBadgeClass(s) {
  if (s === 'active') return 'bg-green-100 text-green-700';
  if (s === 'trialing') return 'bg-blue-100 text-blue-700';
  if (s === 'past_due') return 'bg-amber-100 text-amber-700';
  if (s === 'expired') return 'bg-red-100 text-red-700';
  if (s === 'cancelled') return 'bg-gray-100 text-gray-500';
  return 'bg-blue-100 text-blue-700';
}

function txTypeLabel(type) {
  const map = {
    subscription: t('crm.billing.txSubscription'),
    activation_fee: t('crm.billing.txOnboarding'),
    commission: t('crm.billing.txCommission'),
    earn_first: t('crm.billing.txAutoDebit'),
    payout: t('crm.billing.txPayout'),
    refund: t('crm.billing.txRefund'),
  };
  return map[type] || type || '--';
}

function txStatusLabel(s) {
  const map = {
    succeeded: t('crm.billing.txPaid'),
    completed: t('crm.billing.txPaid'),
    pending: t('crm.billing.txPending'),
    failed: t('crm.billing.txFailed'),
    refunded: t('crm.billing.txRefunded'),
  };
  return map[s] || s || '--';
}

function txStatusClass(s) {
  if (s === 'succeeded' || s === 'completed') return 'bg-green-100 text-green-700';
  if (s === 'pending') return 'bg-yellow-100 text-yellow-700';
  if (s === 'failed') return 'bg-red-100 text-red-700';
  return 'bg-gray-100 text-gray-500';
}

function fmtMoney(val) {
  if (!val && val !== 0) return '0 ' + t('crm.billing.sum');
  return Number(val).toLocaleString('uz-UZ') + ' ' + t('crm.billing.sum');
}


const managersPct = computed(() => {
  if (!limits.value?.max_managers) return 0;
  return Math.min(100, Math.round((limits.value.managers_count / limits.value.max_managers) * 100));
});

const casesPct = computed(() => {
  if (!limits.value?.max_cases) return 0;
  return Math.min(100, Math.round((limits.value.cases_count / limits.value.max_cases) * 100));
});

const leadsPct = computed(() => {
  if (!limits.value?.max_leads_per_month) return 0;
  return Math.min(100, Math.round(((limits.value.leads_this_month || 0) / limits.value.max_leads_per_month) * 100));
});

function showToast(msg, isError = false) {
  toast.value = msg;
  toastError.value = isError;
  setTimeout(() => { toast.value = ''; }, 4000);
}

function openChangePlan(plan) {
  if (isCurrentPlan(plan.slug)) return;
  selectedPlan.value = plan;
  showConfirmModal.value = true;
}

async function confirmChangePlan() {
  if (!selectedPlan.value || changingPlan.value) return;
  changingPlan.value = true;

  try {
    const res = await api.post('/billing/change-plan', {
      plan_slug: selectedPlan.value.slug,
      billing_period: selectedPeriod.value,
    });

    const data = res.data?.data ?? res.data;
    showConfirmModal.value = false;

    if (data.type === 'downgrade_scheduled') {
      const dateStr = data.change_at ? new Date(data.change_at).toLocaleDateString('uz-UZ') : '';
      showToast(t('crm.billing.downgradePlanned', { plan: data.plan_name, date: dateStr }));
    } else {
      const creditMsg = data.credit > 0 ? ` (${t('crm.billing.credit', { amount: fmtMoney(data.credit) })})` : '';
      showToast(t('crm.billing.planChanged', { plan: data.plan_name }) + creditMsg);
    }

    if (data.warnings?.length) {
      setTimeout(() => showToast(data.warnings[0], true), 2000);
    }

    await reloadBillingData();
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors?.[Object.keys(err.response?.data?.errors || {})[0]]?.[0] || t('crm.billing.changePlanError');
    showToast(msg, true);
  } finally {
    changingPlan.value = false;
  }
}

async function cancelDowngrade() {
  try {
    await api.post('/billing/cancel-downgrade');
    showToast(t('crm.billing.downgradeCancelled'));
    await reloadBillingData();
  } catch (err) {
    showToast(err.response?.data?.message || t('crm.billing.changePlanError'), true);
  }
}

async function reloadBillingData() {
  const [subRes, limRes] = await Promise.allSettled([
    api.get('/billing/subscription'),
    api.get('/billing/limits'),
  ]);
  if (subRes.status === 'fulfilled') {
    const d = subRes.value.data?.data ?? subRes.value.data ?? {};
    sub.value = { ...sub.value, ...d };
  }
  if (limRes.status === 'fulfilled') {
    limits.value = limRes.value.data?.data ?? limRes.value.data ?? null;
  }
}

onMounted(async () => {
  // Проверить URL-параметр payment (после возврата с оплаты)
  const urlParams = new URLSearchParams(window.location.search);
  const paymentParam = urlParams.get('payment');
  if (paymentParam === 'success') {
    lastPaymentStatus.value = 'completed';
    showToast(t('crm.billing.paymentCompleted'));
    // Убрать параметр из URL
    window.history.replaceState({}, '', window.location.pathname);
  } else if (paymentParam === 'pending') {
    lastPaymentStatus.value = 'pending';
    showToast(t('crm.billing.paymentPending'));
    window.history.replaceState({}, '', window.location.pathname);
  }

  try {
    const [subRes, limRes, txRes, plansRes] = await Promise.allSettled([
      api.get('/billing/subscription'),
      api.get('/billing/limits'),
      api.get('/billing/transactions'),
      api.get('/billing/plans'),
    ]);

    if (subRes.status === 'fulfilled') {
      const data = subRes.value.data?.data ?? subRes.value.data ?? {};
      sub.value = { ...sub.value, ...data };
    }

    if (limRes.status === 'fulfilled') {
      limits.value = limRes.value.data?.data ?? limRes.value.data ?? null;
    }

    if (txRes.status === 'fulfilled') {
      const raw = txRes.value.data?.data ?? txRes.value.data ?? [];
      transactions.value = Array.isArray(raw) ? raw : [];
    }

    if (plansRes.status === 'fulfilled') {
      const raw = plansRes.value.data?.data ?? plansRes.value.data ?? [];
      availablePlans.value = (Array.isArray(raw) ? raw : []).filter(p => p.is_active !== false && p.is_public !== false);
    }
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
});
</script>
