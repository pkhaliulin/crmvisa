<template>
  <div class="bg-white rounded-xl border border-gray-100">
    <button @click="open = !open"
      class="flex items-center justify-between w-full px-4 py-3 text-left hover:bg-gray-50/50 transition-colors rounded-xl">
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.engine.guidance') }}</h3>
        <span v-if="tipsCount" class="text-[10px] px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-600 font-medium tabular-nums">{{ tipsCount }}</span>
      </div>
      <svg :class="['w-4 h-4 text-gray-400 transition-transform duration-200', open ? 'rotate-180' : '']"
        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <div v-if="open" class="px-4 pb-4">
      <div v-if="loading" class="flex justify-center py-4">
        <div class="animate-spin w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
      </div>

      <template v-else>
        <!-- Stage tips -->
        <div v-if="stageTips.length" class="mb-3">
          <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-2">
            {{ t('crm.engine.currentStageTips') }}
          </p>
          <div class="space-y-2">
            <div v-for="(tip, i) in stageTips" :key="'s'+i"
              class="flex gap-2.5 p-2.5 rounded-lg bg-blue-50/50 border border-blue-100/50">
              <div class="shrink-0 w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center mt-0.5">
                <span class="text-[10px] font-bold text-blue-600">{{ i + 1 }}</span>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-800 leading-snug">{{ tip.title }}</p>
                <p v-if="tip.description" class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ tip.description }}</p>
                <p v-if="tip.warning" class="text-[10px] text-red-500 font-medium mt-1">{{ tip.warning }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Rule info -->
        <div v-if="ruleInfo" class="mb-3 p-3 rounded-lg bg-gray-50/80 border border-gray-100">
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div>
              <span class="text-gray-400">{{ t('crm.engine.processingTime') }}</span>
              <p class="font-medium text-gray-700">{{ ruleInfo.processing_days }} {{ t('crm.engine.days') }}</p>
            </div>
            <div>
              <span class="text-gray-400">{{ t('crm.engine.consularFee') }}</span>
              <p class="font-medium text-gray-700">{{ ruleInfo.consular_fee }} EUR</p>
            </div>
            <div>
              <span class="text-gray-400">{{ t('crm.engine.serviceFee') }}</span>
              <p class="font-medium text-gray-700">{{ ruleInfo.service_fee }} EUR</p>
            </div>
            <div>
              <span class="text-gray-400">{{ t('crm.engine.maxStay') }}</span>
              <p class="font-medium text-gray-700">{{ ruleInfo.max_stay_days }} {{ t('crm.engine.days') }}</p>
            </div>
          </div>
          <p v-if="ruleInfo.notes" class="text-[10px] text-gray-500 mt-2 border-t border-gray-100 pt-2">{{ ruleInfo.notes }}</p>
        </div>

        <!-- General tips -->
        <div v-if="generalTips.length" class="mb-3">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
            {{ t('crm.engine.generalTips') }}
          </p>
          <div class="space-y-1.5">
            <div v-for="(tip, i) in generalTips" :key="'g'+i"
              class="flex gap-2 p-2 rounded-lg hover:bg-gray-50 transition-colors">
              <span class="w-1.5 h-1.5 rounded-full bg-gray-300 mt-1.5 shrink-0"></span>
              <div class="min-w-0">
                <p class="text-xs text-gray-600 leading-snug">{{ tip.title }}</p>
                <p v-if="tip.description" class="text-[10px] text-gray-400 mt-0.5">{{ tip.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- KB articles -->
        <div v-if="articles.length">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
            {{ t('crm.engine.knowledgeBase') }}
          </p>
          <div class="space-y-1">
            <button v-for="article in articles" :key="article.id"
              @click="$emit('openArticle', article.id)"
              class="w-full flex items-center gap-2 p-2 rounded-lg text-left hover:bg-gray-50 transition-colors group">
              <span :class="['text-[10px] px-1.5 py-0.5 rounded font-medium shrink-0', categoryColor(article.category)]">
                {{ categoryLabel(article.category) }}
              </span>
              <span class="text-xs text-gray-600 group-hover:text-blue-600 transition-colors truncate">{{ article.title }}</span>
            </button>
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="!stageTips.length && !generalTips.length && !articles.length && !ruleInfo" class="text-center py-4">
          <p class="text-xs text-gray-400">{{ t('crm.engine.noGuidance') }}</p>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { caseEngineApi } from '../../api/caseEngine'

const { t } = useI18n()

const props = defineProps({
  caseId: { type: String, required: true },
  stage: { type: String, default: null },
})

defineEmits(['openArticle'])

const loading = ref(false)
const open = ref(false)
const stageTips = ref([])
const generalTips = ref([])
const articles = ref([])
const ruleInfo = ref(null)

const tipsCount = computed(() => stageTips.value.length + generalTips.value.length)

const CATEGORY_COLORS = {
  country_guide: 'bg-green-50 text-green-600',
  visa_process:  'bg-blue-50 text-blue-600',
  documents:     'bg-purple-50 text-purple-600',
  requirements:  'bg-orange-50 text-orange-600',
  faq:           'bg-gray-100 text-gray-600',
  tips:          'bg-yellow-50 text-yellow-700',
  common_mistakes: 'bg-red-50 text-red-600',
  finance:       'bg-emerald-50 text-emerald-600',
  changes:       'bg-pink-50 text-pink-600',
}

const CATEGORY_LABELS = {
  country_guide: 'Guide',
  visa_process:  'Process',
  documents:     'Docs',
  requirements:  'Req',
  faq:           'FAQ',
  tips:          'Tips',
  common_mistakes: 'Warn',
  finance:       'Finance',
  changes:       'News',
}

function categoryColor(cat) {
  return CATEGORY_COLORS[cat] || 'bg-gray-100 text-gray-600'
}
function categoryLabel(cat) {
  return CATEGORY_LABELS[cat] || cat
}

async function loadGuidance() {
  loading.value = true
  try {
    const { data } = await caseEngineApi.guidance(props.caseId)
    const d = data.data
    stageTips.value = d.stage_tips ?? []
    generalTips.value = d.general_tips ?? []
    articles.value = d.articles ?? []
    ruleInfo.value = d.rule_info ?? null
  } catch {
    stageTips.value = []
    generalTips.value = []
    articles.value = []
    ruleInfo.value = null
  } finally {
    loading.value = false
  }
}

function refresh() {
  loadGuidance()
}

defineExpose({ refresh })

onMounted(loadGuidance)

watch(() => props.stage, () => {
  loadGuidance()
})
</script>
