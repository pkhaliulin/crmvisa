<template>
  <div class="bg-white rounded-xl border border-gray-100">
    <button @click="open = !open"
      class="flex items-center justify-between w-full px-4 py-3 text-left hover:bg-gray-50/50 transition-colors rounded-xl">
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.engine.checkpoints') }}</h3>
        <span v-if="totalCount" class="text-xs text-gray-300 tabular-nums">{{ doneCount }}/{{ totalCount }}</span>
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
        <div v-for="(items, stage) in groupedCheckpoints" :key="stage" class="mb-3 last:mb-0">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">{{ stage }}</p>
          <div class="space-y-1.5">
            <label v-for="cp in items" :key="cp.id"
              :class="['flex items-start gap-2.5 p-2 rounded-lg cursor-pointer transition-colors group',
                cp.is_completed ? 'bg-green-50/50' : cp.is_blocking ? 'hover:bg-red-50/30' : 'hover:bg-gray-50']">
              <input type="checkbox" :checked="cp.is_completed"
                @change="toggle(cp)"
                :class="['w-4 h-4 rounded mt-0.5 shrink-0 cursor-pointer',
                  cp.is_blocking && !cp.is_completed ? 'border-red-300 text-red-500 focus:ring-red-500' : 'border-gray-300 text-green-500 focus:ring-green-500']" />
              <div class="min-w-0">
                <span :class="['text-sm leading-snug', cp.is_completed ? 'text-gray-400 line-through' : 'text-gray-700']">
                  {{ cp.title }}
                </span>
                <span v-if="cp.is_blocking && !cp.is_completed"
                  class="ml-1.5 text-[9px] text-red-500 font-bold uppercase">{{ t('crm.engine.blocking') }}</span>
                <p v-if="cp.description" class="text-[10px] text-gray-400 mt-0.5">{{ cp.description }}</p>
              </div>
            </label>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { caseEngineApi } from '../../api/caseEngine'

const { t } = useI18n()

const props = defineProps({
  caseId: { type: String, required: true },
})

const emit = defineEmits(['updated'])

const loading = ref(false)
const open = ref(true)
const checkpoints = ref({})

const allItems = computed(() => Object.values(checkpoints.value).flat())
const totalCount = computed(() => allItems.value.length)
const doneCount = computed(() => allItems.value.filter(c => c.is_completed).length)
const groupedCheckpoints = computed(() => checkpoints.value)

async function loadCheckpoints() {
  loading.value = true
  try {
    const { data } = await caseEngineApi.checkpoints(props.caseId)
    checkpoints.value = data.data ?? {}
  } catch {
    checkpoints.value = {}
  } finally {
    loading.value = false
  }
}

async function toggle(cp) {
  const newVal = !cp.is_completed
  cp.is_completed = newVal // optimistic
  try {
    await caseEngineApi.toggleCheckpoint(props.caseId, cp.checkpoint_id, {
      is_completed: newVal,
    })
    emit('updated')
  } catch {
    cp.is_completed = !newVal // rollback
  }
}

function refresh() {
  loadCheckpoints()
}

defineExpose({ refresh })

onMounted(loadCheckpoints)
</script>
