<template>
  <div :class="['border rounded-lg transition-all duration-200 hover:shadow-sm', borderClass]">
    <div class="flex items-start gap-3 p-3">
      <!-- Status bar -->
      <div :class="['w-1 rounded-full self-stretch shrink-0', barClass]"></div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <!-- Name + badge -->
        <div class="flex items-center gap-2 flex-wrap">
          <p class="text-sm font-medium text-gray-800">{{ item.name }}</p>
          <span v-if="!item.is_required" class="text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">
            {{ t('crm.doc.optional') }}
          </span>
          <AppBadge :color="statusColor">{{ statusLabel }}</AppBadge>
        </div>

        <!-- Description -->
        <p v-if="item.description" class="text-xs text-gray-400 mt-0.5 leading-relaxed">{{ item.description }}</p>

        <!-- Uploaded file -->
        <div v-if="item.document" class="mt-1.5 flex items-center gap-2 flex-wrap">
          <button @click="$emit('preview', item.document)"
            class="text-xs text-blue-600 hover:text-blue-800 font-medium truncate max-w-[200px]">
            {{ item.document.original_name }}
          </button>
          <a v-if="isSafeUrl(item.document.url)" :href="item.document.url" download class="text-[10px] text-gray-400 hover:text-gray-600">{{ t('crm.doc.download') }}</a>
          <label class="text-[10px] text-gray-400 hover:text-gray-600 cursor-pointer">
            {{ t('crm.doc.replace') }}
            <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.tiff,.bmp" @change="$emit('upload', item, $event)" />
          </label>
        </div>

        <!-- Reject note -->
        <p v-if="item.notes && item.status === 'rejected'"
          class="mt-1.5 text-xs text-red-600 bg-red-50 rounded px-2 py-1">
          {{ item.notes }}
        </p>

        <!-- Review note -->
        <p v-if="item.review_notes && item.status !== 'rejected'"
          class="mt-1.5 text-xs text-gray-500 bg-gray-50 rounded px-2 py-1">
          {{ item.review_notes }}
        </p>

        <!-- Manager hints (collapsible) -->
        <div v-if="item.manager_instructions" class="mt-2">
          <button @click="hintsOpen = !hintsOpen"
            class="flex items-center gap-1 text-[10px] font-semibold text-amber-600 hover:text-amber-700 uppercase tracking-wider">
            <svg :class="['w-3 h-3 transition-transform', hintsOpen ? 'rotate-90' : '']" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            {{ t('crm.doc.managerHints') }}
          </button>
          <div v-if="hintsOpen" class="mt-1.5 text-xs text-gray-600 bg-amber-50/60 border border-amber-100 rounded-lg p-2.5 whitespace-pre-line leading-relaxed">{{ item.manager_instructions }}</div>
        </div>

        <!-- Stop / Risk / Success factors -->
        <div v-if="hintsOpen && (item.ai_stop_factors?.length || item.ai_risk_indicators?.length || item.ai_success_factors?.length)"
          class="mt-1.5 flex flex-wrap gap-1.5">
          <span v-for="f in (item.ai_stop_factors || [])" :key="'s'+f"
            class="text-[10px] px-1.5 py-0.5 rounded bg-red-50 text-red-600 border border-red-100">{{ f }}</span>
          <span v-for="f in (item.ai_risk_indicators || [])" :key="'r'+f"
            class="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-600 border border-amber-100">{{ f }}</span>
          <span v-for="f in (item.ai_success_factors || [])" :key="'g'+f"
            class="text-[10px] px-1.5 py-0.5 rounded bg-green-50 text-green-600 border border-green-100">{{ f }}</span>
        </div>

        <!-- AI Analysis panel -->
        <DocAiPanel v-if="item.document && item.type === 'upload'"
          :analysis="item.ai_analysis"
          :confidence="item.ai_confidence ?? 0"
          :loading="aiLoading"
          :item-id="item.id"
          @analyze="$emit('ai-analyze', item)" />

        <!-- Translation block -->
        <div v-if="item.review_status === 'needs_translation'"
          class="mt-2 p-2.5 bg-purple-50/60 rounded-lg border border-purple-100">
          <div class="flex items-center gap-2 text-xs text-purple-700">
            <span>{{ t('crm.doc.translationPages', { n: item.translation_pages ?? '?' }) }}</span>
            <span v-if="item.translation_price"> {{ t('crm.doc.translationPrice', { price: item.translation_price.toLocaleString() }) }}</span>
          </div>

          <!-- Upload translation -->
          <label v-if="item.status === 'needs_translation'"
            class="mt-2 inline-block cursor-pointer text-xs px-3 py-1.5 rounded-lg border border-purple-200 text-purple-700 bg-purple-100 hover:bg-purple-200 font-medium transition-colors">
            {{ t('crm.doc.uploadTranslation') }}
            <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.tiff,.bmp" @change="$emit('upload-translation', item, $event)" />
          </label>

          <!-- Approve translation -->
          <div v-if="item.status === 'translated'" class="mt-2 flex items-center gap-2">
            <span class="text-xs text-purple-600">{{ t('crm.doc.translationUploaded') }}</span>
            <button @click="$emit('approve-translation', item)"
              class="text-xs px-3 py-1.5 rounded-lg bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 font-medium transition-colors">
              {{ t('crm.doc.approve') }}
            </button>
          </div>

          <p v-if="item.status === 'translation_approved'" class="mt-1 text-xs text-green-600 font-medium">
            {{ t('crm.doc.translationApproved') }}
          </p>
        </div>
      </div>

      <!-- Right: actions -->
      <div class="shrink-0 flex items-center gap-1.5 flex-wrap justify-end">
        <!-- Checkbox toggle -->
        <button v-if="item.type === 'checkbox'" @click="$emit('toggle', item)"
          :class="['text-xs px-2.5 py-1 rounded-lg border font-medium transition-colors',
            item.is_checked ? 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100' : 'border-gray-200 text-gray-600 hover:bg-gray-50']">
          {{ item.is_checked ? t('crm.doc.done') : t('crm.doc.mark') }}
        </button>

        <!-- Upload (empty slot) -->
        <label v-if="!item.document && item.type !== 'checkbox' && !['approved','needs_translation','translated','translation_approved'].includes(item.status)"
          class="cursor-pointer text-xs px-2.5 py-1 rounded-lg border border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100 font-medium transition-colors">
          {{ t('crm.doc.upload') }}
          <input type="file" class="hidden" @change="$emit('upload', item, $event)" />
        </label>

        <!-- Review buttons (manager) -->
        <template v-if="item.document && item.status === 'uploaded'">
          <button @click="$emit('review', item, 'approved')"
            class="text-xs px-2 py-1 rounded-lg bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 transition-colors">
            {{ t('crm.doc.ok') }}
          </button>
          <button @click="$emit('translation', item)"
            class="text-xs px-2 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition-colors">
            {{ t('crm.doc.translation') }}
          </button>
          <button @click="$emit('reject', item)"
            class="text-xs px-2 py-1 rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition-colors">
            {{ t('crm.doc.no') }}
          </button>
        </template>

        <!-- Repeat -->
        <button v-if="item.is_repeatable" @click="$emit('repeat', item)"
          class="text-xs text-gray-400 hover:text-blue-600 px-1 py-0.5 rounded border border-dashed border-gray-300 hover:border-blue-400 transition-colors">
          +1
        </button>

        <!-- Delete custom -->
        <button v-if="!item.requirement_id && !item.country_requirement_id" @click="$emit('delete', item)"
          class="text-gray-300 hover:text-red-400 text-xs px-1 transition-colors">
          x
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppBadge from './AppBadge.vue';
import DocAiPanel from './DocAiPanel.vue';

const { t } = useI18n();
const props = defineProps({ item: Object, aiLoading: { type: Boolean, default: false } });
defineEmits(['upload', 'toggle', 'review', 'reject', 'translation', 'upload-translation', 'approve-translation', 'preview', 'delete', 'repeat', 'ai-analyze']);

const hintsOpen = ref(false);

const STATUS_MAP = computed(() => ({
  approved:             { color: 'green',  label: t('crm.doc.statusAccepted'),       border: 'border-green-100 bg-green-50/30',   bar: 'bg-green-400' },
  translation_approved: { color: 'green',  label: t('crm.doc.statusTranslationOk'),  border: 'border-green-100 bg-green-50/30',   bar: 'bg-green-400' },
  rejected:             { color: 'red',    label: t('crm.doc.statusRejected'),        border: 'border-red-100 bg-red-50/20',       bar: 'bg-red-400' },
  needs_translation:    { color: 'purple', label: t('crm.doc.statusForTranslation'),  border: 'border-purple-100 bg-purple-50/20', bar: 'bg-purple-400' },
  translated:           { color: 'purple', label: t('crm.doc.statusTranslated'),      border: 'border-purple-100 bg-purple-50/20', bar: 'bg-purple-400' },
  uploaded:             { color: 'blue',   label: t('crm.doc.statusReview'),           border: 'border-blue-100 bg-blue-50/20',     bar: 'bg-blue-400' },
}));

const statusEntry = computed(() => {
  const s = props.item.status;
  if (STATUS_MAP.value[s]) return STATUS_MAP.value[s];
  if (props.item.is_checked) return { color: 'blue', label: t('crm.doc.statusReview'), border: 'border-gray-100', bar: 'bg-blue-400' };
  return { color: 'gray', label: props.item.is_required ? t('common.required', 'Ожидает') : t('crm.doc.statusNotUploaded'), border: 'border-gray-100', bar: 'bg-gray-200' };
});

const statusColor = computed(() => statusEntry.value.color);
const statusLabel = computed(() => statusEntry.value.label);
const borderClass = computed(() => statusEntry.value.border);
const barClass    = computed(() => statusEntry.value.bar);

function isSafeUrl(url) {
  if (!url) return false;
  try { const u = new URL(url, window.location.origin); return ['http:', 'https:'].includes(u.protocol); }
  catch { return false; }
}
</script>
