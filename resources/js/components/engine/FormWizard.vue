<template>
  <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50">
      <div class="flex items-center gap-3">
        <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.engine.formHelper') }}</h3>
        <span v-if="progress" class="text-xs text-gray-400 tabular-nums">{{ progress.percent }}%</span>
      </div>
      <div class="flex items-center gap-2">
        <button @click="copyAllStep" v-if="currentStepData"
          class="text-[10px] text-gray-500 hover:text-blue-600 font-medium px-2 py-1 rounded hover:bg-blue-50 transition-colors">
          {{ copyAllLabel }}
        </button>
        <button @click="doPrefill" :disabled="prefilling"
          class="text-[10px] text-blue-600 hover:text-blue-700 font-medium px-2 py-1 rounded hover:bg-blue-50 transition-colors disabled:opacity-50">
          {{ prefilling ? '...' : t('crm.engine.autofill') }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else-if="steps.length">
      <!-- Step tabs -->
      <div class="flex border-b border-gray-100 overflow-x-auto scrollbar-hide">
        <button v-for="(step, i) in steps" :key="step.step"
          @click="currentStep = step.step"
          :class="['px-4 py-2.5 text-xs font-medium whitespace-nowrap border-b-2 transition-colors',
            currentStep === step.step
              ? 'border-blue-500 text-blue-600'
              : step.complete ? 'border-transparent text-green-600 hover:text-green-700' : 'border-transparent text-gray-400 hover:text-gray-600']">
          <span class="mr-1">{{ step.step }}.</span>
          {{ step.title }}
          <span v-if="step.complete" class="ml-1 text-green-500">&#10003;</span>
          <span v-else class="ml-1 text-[10px] text-gray-300">{{ step.filled }}/{{ step.total }}</span>
        </button>
      </div>

      <!-- Fields -->
      <div class="p-5">
        <div v-if="currentStepData" class="space-y-3">
          <div v-for="field in currentStepData.fields" :key="field.key" class="group">
            <!-- Text / Date -->
            <template v-if="field.type === 'text' || field.type === 'date' || field.type === 'textarea'">
              <label class="flex items-baseline gap-1 mb-1">
                <span class="text-xs text-gray-500 font-medium">{{ field.label }}</span>
                <span v-if="field.required" class="text-red-400 text-xs">*</span>
              </label>
              <div class="flex gap-2">
                <input v-if="field.type !== 'textarea'"
                  :type="field.type === 'date' ? 'date' : 'text'"
                  v-model="formValues[field.key]"
                  @blur="saveField(field.key)"
                  :class="['flex-1 text-sm border rounded-lg px-3 py-2 outline-none transition-colors',
                    field.value ? 'border-green-200 bg-green-50/30' : 'border-gray-200 focus:border-blue-400 focus:ring-1 focus:ring-blue-100']" />
                <textarea v-else
                  v-model="formValues[field.key]"
                  @blur="saveField(field.key)"
                  rows="2"
                  :class="['flex-1 text-sm border rounded-lg px-3 py-2 outline-none transition-colors resize-none',
                    field.value ? 'border-green-200 bg-green-50/30' : 'border-gray-200 focus:border-blue-400']" />
                <button @click="copyField(field.key)"
                  :class="['shrink-0 w-8 h-8 flex items-center justify-center rounded-lg border transition-all duration-200',
                    copiedKey === field.key
                      ? 'border-green-300 bg-green-50 text-green-600'
                      : 'border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50']"
                  :title="t('crm.engine.copyToClipboard')">
                  <svg v-if="copiedKey === field.key" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                  <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                </button>
              </div>
              <p v-if="field.help_text" class="text-[10px] text-gray-400 mt-1">{{ field.help_text }}</p>
            </template>

            <!-- Select -->
            <template v-if="field.type === 'select'">
              <label class="flex items-baseline gap-1 mb-1">
                <span class="text-xs text-gray-500 font-medium">{{ field.label }}</span>
                <span v-if="field.required" class="text-red-400 text-xs">*</span>
              </label>
              <div class="flex gap-2">
                <select v-model="formValues[field.key]" @change="saveField(field.key)"
                  :class="['flex-1 text-sm border rounded-lg px-3 py-2 outline-none transition-colors',
                    formValues[field.key] ? 'border-green-200 bg-green-50/30' : 'border-gray-200 focus:border-blue-400']">
                  <option value="">--</option>
                  <option v-for="(label, val) in (field.options || {})" :key="val" :value="val">{{ label }}</option>
                </select>
                <button @click="copyField(field.key)"
                  :class="['shrink-0 w-8 h-8 flex items-center justify-center rounded-lg border transition-all duration-200',
                    copiedKey === field.key
                      ? 'border-green-300 bg-green-50 text-green-600'
                      : 'border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50']">
                  <svg v-if="copiedKey === field.key" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                  <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                </button>
              </div>
            </template>

            <!-- Radio -->
            <template v-if="field.type === 'radio'">
              <label class="flex items-baseline gap-1 mb-1">
                <span class="text-xs text-gray-500 font-medium">{{ field.label }}</span>
                <span v-if="field.required" class="text-red-400 text-xs">*</span>
              </label>
              <div class="flex gap-3">
                <label v-for="(label, val) in (field.options || {})" :key="val"
                  class="flex items-center gap-1.5 cursor-pointer">
                  <input type="radio" :name="field.key" :value="val"
                    v-model="formValues[field.key]" @change="saveField(field.key)"
                    class="w-3.5 h-3.5 text-blue-500 border-gray-300 focus:ring-blue-500" />
                  <span class="text-xs text-gray-600">{{ label }}</span>
                </label>
              </div>
            </template>

            <!-- Checkbox (multi-select) -->
            <template v-if="field.type === 'checkbox'">
              <label class="flex items-baseline gap-1 mb-1">
                <span class="text-xs text-gray-500 font-medium">{{ field.label }}</span>
                <span v-if="field.required" class="text-red-400 text-xs">*</span>
              </label>
              <div class="flex flex-wrap gap-2">
                <label v-for="(label, val) in (field.options || {})" :key="val"
                  class="flex items-center gap-1.5 cursor-pointer">
                  <input type="checkbox" :value="val"
                    v-model="checkboxValues[field.key]" @change="saveCheckboxField(field.key)"
                    class="w-3.5 h-3.5 rounded text-blue-500 border-gray-300 focus:ring-blue-500" />
                  <span class="text-xs text-gray-600">{{ label }}</span>
                </label>
              </div>
            </template>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="text-center py-8">
      <p class="text-xs text-gray-400">{{ t('crm.engine.noFormFields') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { caseEngineApi } from '../../api/caseEngine'

const { t } = useI18n()

const props = defineProps({
  caseId: { type: String, required: true },
})

const emit = defineEmits(['updated'])

const loading = ref(false)
const prefilling = ref(false)
const steps = ref([])
const progress = ref(null)
const currentStep = ref(1)
const formValues = reactive({})
const checkboxValues = reactive({})
const copiedKey = ref(null)
const copyAllDone = ref(false)

const currentStepData = computed(() => steps.value.find(s => s.step === currentStep.value))

let saveTimer = null

async function loadForm() {
  loading.value = true
  try {
    const [formRes, progRes] = await Promise.all([
      caseEngineApi.getForm(props.caseId),
      caseEngineApi.formProgress(props.caseId),
    ])

    steps.value = progRes.data?.data?.steps ?? formRes.data?.data ?? []
    progress.value = progRes.data?.data ?? null

    // Populate form values
    for (const step of steps.value) {
      for (const field of step.fields) {
        if (field.type === 'checkbox') {
          checkboxValues[field.key] = Array.isArray(field.value) ? [...field.value] : []
        } else {
          formValues[field.key] = field.value ?? ''
        }
      }
    }

    if (steps.value.length && !steps.value.find(s => s.step === currentStep.value)) {
      currentStep.value = steps.value[0].step
    }
  } catch {
    steps.value = []
  } finally {
    loading.value = false
  }
}

function saveField(key) {
  clearTimeout(saveTimer)
  saveTimer = setTimeout(() => {
    doSaveStep()
  }, 300)
}

function saveCheckboxField(key) {
  formValues[key] = [...(checkboxValues[key] ?? [])]
  saveField(key)
}

async function doSaveStep() {
  const stepFields = currentStepData.value?.fields ?? []
  const data = {}
  for (const f of stepFields) {
    if (formValues[f.key] !== undefined && formValues[f.key] !== '') {
      data[f.key] = formValues[f.key]
    }
  }

  try {
    await caseEngineApi.saveFormStep(props.caseId, currentStep.value, data)
    emit('updated')
    // Refresh progress
    const { data: progData } = await caseEngineApi.formProgress(props.caseId)
    progress.value = progData?.data ?? null
    if (progress.value?.steps) {
      steps.value = progress.value.steps
    }
  } catch { /* ignore */ }
}

async function doPrefill() {
  prefilling.value = true
  try {
    await caseEngineApi.prefillForm(props.caseId)
    await loadForm()
    emit('updated')
  } finally {
    prefilling.value = false
  }
}

let copiedTimer = null

function copyField(key) {
  const val = formValues[key]
  if (val) {
    navigator.clipboard.writeText(String(val))
    copiedKey.value = key
    clearTimeout(copiedTimer)
    copiedTimer = setTimeout(() => { copiedKey.value = null }, 1500)
  }
}

const copyAllLabel = computed(() =>
  copyAllDone.value ? t('crm.engine.copied') : t('crm.engine.copyAllStep')
)

function copyAllStep() {
  const fields = currentStepData.value?.fields ?? []
  const lines = []
  for (const f of fields) {
    const val = formValues[f.key]
    if (val) {
      lines.push(`${f.label}: ${val}`)
    }
  }
  if (lines.length) {
    navigator.clipboard.writeText(lines.join('\n'))
    copyAllDone.value = true
    setTimeout(() => { copyAllDone.value = false }, 2000)
  }
}

function refresh() {
  loadForm()
}

defineExpose({ refresh })

onMounted(loadForm)
</script>
