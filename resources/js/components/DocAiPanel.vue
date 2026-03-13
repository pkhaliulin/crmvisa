<template>
  <div class="mt-2">
    <!-- Trigger button when no analysis -->
    <button v-if="!analysis && !loading" @click="$emit('analyze')"
      class="text-xs px-3 py-1.5 rounded-lg bg-violet-100 text-violet-700 border border-violet-200 hover:bg-violet-200 font-medium transition-colors">
      {{ t('crm.docAi.analyze') }}
    </button>

    <!-- Loading state -->
    <button v-if="loading" disabled
      class="text-xs px-3 py-1.5 rounded-lg bg-violet-100 text-violet-500 border border-violet-200 font-medium flex items-center gap-1.5 cursor-wait">
      <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      {{ t('crm.docAi.analyzing') }}
    </button>

    <!-- Analysis results -->
    <div v-if="analysis && !loading" class="rounded-lg border border-violet-200 bg-violet-50/60 overflow-hidden">
      <!-- Header (collapsible) -->
      <button @click="expanded = !expanded"
        class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium text-violet-700 hover:bg-violet-100/60 transition-colors">
        <div class="flex items-center gap-2">
          <span>{{ t('crm.docAi.analyze') }}</span>
          <span :class="['px-1.5 py-0.5 rounded text-[10px] font-semibold', confidenceBadgeClass]">
            {{ confidence }}%
          </span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', expanded ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      <!-- Expandable content -->
      <transition
        enter-active-class="transition-all duration-200 ease-out"
        leave-active-class="transition-all duration-200 ease-in"
        enter-from-class="max-h-0 opacity-0"
        enter-to-class="max-h-[2000px] opacity-100"
        leave-from-class="max-h-[2000px] opacity-100"
        leave-to-class="max-h-0 opacity-0">
        <div v-show="expanded" class="px-3 pb-3 space-y-3 overflow-hidden">

          <!-- Confidence bar -->
          <div>
            <div class="flex items-center justify-between text-[10px] text-gray-500 mb-1">
              <span>{{ t('crm.docAi.confidence') }}</span>
              <span :class="confidenceTextClass">{{ confidence }}%</span>
            </div>
            <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
              <div :class="['h-full rounded-full transition-all duration-500', confidenceBarClass]"
                :style="{ width: confidence + '%' }"></div>
            </div>
          </div>

          <!-- Person mismatch alert -->
          <div v-if="analysis.person_mismatch"
            class="flex items-start gap-2 p-2.5 rounded-lg bg-red-100 border border-red-300 text-red-800">
            <svg class="w-5 h-5 shrink-0 text-red-600 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div>
              <p class="text-xs font-bold">{{ t('crm.docAi.wrongPerson') }}</p>
              <p class="text-[11px] mt-0.5">
                {{ t('crm.docAi.expectedPerson') }}: <strong>{{ analysis.person_mismatch.expected_person }}</strong>
              </p>
              <p class="text-[11px]">
                {{ t('crm.docAi.foundPerson') }}: <strong>{{ analysis.person_mismatch.document_person }}</strong>
              </p>
            </div>
          </div>

          <!-- Extracted data -->
          <div v-if="analysis.extracted_data && Object.keys(analysis.extracted_data).length">
            <p class="text-[10px] font-semibold text-violet-600 uppercase tracking-wide mb-1">
              {{ t('crm.docAi.extractedData') }}
            </p>
            <table class="w-full text-xs">
              <tr v-for="(val, key) in analysis.extracted_data" :key="key"
                class="border-b border-violet-100 last:border-0">
                <td class="py-1 pr-2 text-gray-500 whitespace-nowrap align-top">{{ key }}</td>
                <td class="py-1 font-medium" :class="val != null ? 'text-gray-800' : 'text-gray-300 italic'">
                  {{ val != null ? val : t('crm.docAi.notExtracted') }}
                </td>
              </tr>
            </table>
          </div>

          <!-- Validation results -->
          <div v-if="analysis.validation_results && analysis.validation_results.length">
            <p class="text-[10px] font-semibold text-violet-600 uppercase tracking-wide mb-1">
              {{ t('crm.docAi.validation') }}
            </p>
            <div class="space-y-1">
              <div v-for="(v, i) in analysis.validation_results" :key="i"
                :class="['flex items-start gap-1.5 text-xs px-2 py-1.5 rounded-md border', validationClass(v.level || v.type)]">
                <span class="shrink-0 mt-0.5" v-html="validationIcon(v.level || v.type)"></span>
                <span>{{ v.message }}</span>
              </div>
            </div>
          </div>

          <!-- Stop factors -->
          <div v-if="analysis.stop_factors && analysis.stop_factors.length">
            <p class="text-[10px] font-semibold text-red-600 uppercase tracking-wide mb-1">
              {{ t('crm.docAi.stopFactors') }}
            </p>
            <div class="space-y-1">
              <div v-for="(f, i) in analysis.stop_factors" :key="i"
                class="text-xs px-2 py-1.5 rounded-md bg-red-50 border border-red-200 text-red-700">
                {{ typeof f === 'string' ? f : f.message }}
              </div>
            </div>
          </div>

          <!-- Success factors -->
          <div v-if="analysis.success_factors && analysis.success_factors.length">
            <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wide mb-1">
              {{ t('crm.docAi.successFactors') }}
            </p>
            <div class="space-y-1">
              <div v-for="(f, i) in analysis.success_factors" :key="i"
                class="text-xs px-2 py-1.5 rounded-md bg-green-50 border border-green-200 text-green-700">
                {{ typeof f === 'string' ? f : f.message }}
              </div>
            </div>
          </div>

          <!-- Risk indicators -->
          <div v-if="analysis.risk_indicators && analysis.risk_indicators.length">
            <p class="text-[10px] font-semibold text-violet-600 uppercase tracking-wide mb-1">
              {{ t('crm.docAi.risks') }}
            </p>
            <div class="space-y-1">
              <div v-for="(r, i) in analysis.risk_indicators" :key="i"
                :class="['flex items-start gap-1.5 text-xs px-2 py-1.5 rounded-md border', riskClass(r.level)]">
                <span class="shrink-0 mt-0.5" v-html="riskIcon(r.level)"></span>
                <span>{{ r.message }}</span>
              </div>
            </div>
          </div>

          <!-- Reanalyze button -->
          <button @click="$emit('analyze')"
            class="text-[10px] text-violet-600 hover:text-violet-800 font-medium transition-colors">
            {{ t('crm.docAi.reanalyze') }}
          </button>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  analysis: { type: Object, default: null },
  confidence: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  itemId: { type: String, default: '' },
});

defineEmits(['analyze']);

const expanded = ref(true);

// Confidence colors
const confidenceBarClass = computed(() => {
  if (props.confidence >= 70) return 'bg-green-500';
  if (props.confidence >= 40) return 'bg-yellow-500';
  return 'bg-red-500';
});

const confidenceTextClass = computed(() => {
  if (props.confidence >= 70) return 'text-green-600 font-semibold';
  if (props.confidence >= 40) return 'text-yellow-600 font-semibold';
  return 'text-red-600 font-semibold';
});

const confidenceBadgeClass = computed(() => {
  if (props.confidence >= 70) return 'bg-green-100 text-green-700';
  if (props.confidence >= 40) return 'bg-yellow-100 text-yellow-700';
  return 'bg-red-100 text-red-700';
});

// Validation helpers
function validationClass(level) {
  if (level === 'critical' || level === 'error') return 'bg-red-50 border-red-200 text-red-700';
  if (level === 'warning') return 'bg-yellow-50 border-yellow-200 text-yellow-700';
  return 'bg-green-50 border-green-200 text-green-700';
}

function validationIcon(level) {
  if (level === 'critical' || level === 'error')
    return '<svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';
  if (level === 'warning')
    return '<svg class="w-3.5 h-3.5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';
  return '<svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
}

// Risk helpers
function riskClass(level) {
  if (level === 'critical') return 'bg-red-50 border-red-200 text-red-700';
  if (level === 'warning') return 'bg-yellow-50 border-yellow-200 text-yellow-700';
  return 'bg-gray-50 border-gray-200 text-gray-600';
}

function riskIcon(level) {
  if (level === 'critical')
    return '<svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';
  if (level === 'warning')
    return '<svg class="w-3.5 h-3.5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';
  return '<svg class="w-3.5 h-3.5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>';
}
</script>
