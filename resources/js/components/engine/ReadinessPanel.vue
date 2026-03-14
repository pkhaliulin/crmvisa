<template>
  <div class="bg-white rounded-xl border border-gray-100 p-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
      <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ t('crm.engine.readiness') }}</p>
      <button v-if="!engineData && !loading" @click="initEngine"
        class="text-[10px] text-blue-600 hover:text-blue-700 font-medium">
        {{ t('crm.engine.initialize') }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-4">
      <div class="animate-spin w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- No engine -->
    <div v-else-if="!engineData" class="text-center py-3">
      <p class="text-xs text-gray-400">{{ t('crm.engine.noRules') }}</p>
    </div>

    <!-- Engine data -->
    <template v-else>
      <!-- Circular progress -->
      <div class="flex items-center gap-4 mb-4">
        <div class="relative w-16 h-16 shrink-0">
          <svg class="w-16 h-16 -rotate-90" viewBox="0 0 36 36">
            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#f3f4f6" stroke-width="3"/>
            <circle cx="18" cy="18" r="15.5" fill="none"
              :stroke="scoreColor" stroke-width="3" stroke-linecap="round"
              :stroke-dasharray="`${score * 0.974} 97.4`"
              class="transition-all duration-700 ease-out"/>
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <span :class="['text-sm font-black tabular-nums', scoreTextColor]">{{ score }}%</span>
          </div>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-bold text-gray-900">
            {{ score >= 100 ? t('crm.engine.ready') : t('crm.engine.inProgress') }}
          </p>
          <p v-if="engineData.next_action" class="text-xs text-gray-500 mt-0.5 line-clamp-2">
            {{ engineData.next_action }}
          </p>
        </div>
      </div>

      <!-- Platform info -->
      <div v-if="engineData.rule" class="flex flex-wrap gap-1.5 mb-3">
        <span v-if="engineData.rule.embassy_platform"
          class="text-[10px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 font-medium">
          {{ engineData.rule.embassy_platform }}
        </span>
        <span v-if="engineData.rule.submission_method"
          class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">
          {{ engineData.rule.submission_method }}
        </span>
        <span v-if="engineData.rule.biometrics_required"
          class="text-[10px] px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 font-medium">
          {{ t('crm.engine.biometrics') }}
        </span>
      </div>

      <!-- Reference number -->
      <div v-if="engineData.rule?.reference_number" class="mb-3 p-2 rounded-lg bg-green-50 border border-green-100">
        <p class="text-[10px] text-green-600 font-medium">{{ t('crm.engine.referenceNumber') }}</p>
        <p class="text-sm font-mono font-bold text-green-800">{{ engineData.rule.reference_number }}</p>
      </div>

      <!-- Missing items -->
      <div v-if="missingItems.length" class="border-t border-gray-100 pt-3">
        <p class="text-[10px] font-bold text-orange-500 uppercase tracking-widest mb-2">
          {{ t('crm.engine.missingItems', { n: missingItems.length }) }}
        </p>
        <div class="space-y-1.5 max-h-40 overflow-y-auto">
          <div v-for="(item, i) in missingItems.slice(0, 8)" :key="i"
            class="flex items-start gap-2">
            <span :class="['w-1.5 h-1.5 rounded-full mt-1.5 shrink-0',
              item.type === 'checkpoint' ? (item.blocking ? 'bg-red-500' : 'bg-orange-400') :
              item.type === 'document' ? 'bg-blue-400' : 'bg-gray-400']"></span>
            <span class="text-xs text-gray-600 leading-snug">{{ docSlugLabel(item.name) }}</span>
          </div>
          <p v-if="missingItems.length > 8" class="text-[10px] text-gray-400">
            {{ t('crm.engine.andMore', { n: missingItems.length - 8 }) }}
          </p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { caseEngineApi } from '../../api/caseEngine'

const { t } = useI18n()

const props = defineProps({
  caseId: { type: String, required: true },
  hasRule: { type: Boolean, default: false },
})

const emit = defineEmits(['initialized', 'updated'])

const loading = ref(false)
const engineData = ref(null)

const docSlugLabels = {
  travel_insurance: 'Медицинская страховка',
  hotel_booking: 'Бронь отеля',
  air_tickets: 'Авиабилеты',
  employment_certificate: 'Справка с работы',
  foreign_passport: 'Загранпаспорт',
  internal_passport: 'Внутренний паспорт',
  income_certificate: 'Справка о доходах',
  bank_balance_certificate: 'Справка об остатке на счёте',
  bank_statement: 'Выписка из банка',
  photo: 'Фото',
  application_form: 'Анкета-заявление',
  invitation_letter: 'Приглашение',
  birth_certificate: 'Свидетельство о рождении',
  marriage_certificate: 'Свидетельство о браке',
  sponsor_letter: 'Спонсорское письмо',
}
function docSlugLabel(name) {
  if (!name) return ''
  return docSlugLabels[name] || name.replace(/_/g, ' ')
}

const score = computed(() => engineData.value?.readiness_score ?? 0)
const missingItems = computed(() => engineData.value?.missing_items ?? [])

const scoreColor = computed(() => {
  if (score.value >= 80) return '#22c55e'
  if (score.value >= 50) return '#3b82f6'
  if (score.value >= 20) return '#f59e0b'
  return '#ef4444'
})

const scoreTextColor = computed(() => {
  if (score.value >= 80) return 'text-green-600'
  if (score.value >= 50) return 'text-blue-600'
  if (score.value >= 20) return 'text-amber-600'
  return 'text-red-600'
})

async function loadReadiness() {
  loading.value = true
  try {
    const { data } = await caseEngineApi.readiness(props.caseId)
    engineData.value = data.data
  } catch (e) {
    // No rules for this country/visa — show init button
    engineData.value = null
  } finally {
    loading.value = false
  }
}

async function initEngine() {
  loading.value = true
  try {
    await caseEngineApi.initialize(props.caseId)
    await loadReadiness()
    emit('initialized')
  } catch {
    engineData.value = null
  } finally {
    loading.value = false
  }
}

function refresh() {
  loadReadiness()
}

defineExpose({ refresh })

onMounted(() => {
  if (props.hasRule) {
    loadReadiness()
  }
})

watch(() => props.hasRule, (v) => {
  if (v) loadReadiness()
})
</script>
