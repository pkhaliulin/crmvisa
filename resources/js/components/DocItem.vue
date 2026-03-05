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
            опц.
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
          <a v-if="isSafeUrl(item.document.url)" :href="item.document.url" download class="text-[10px] text-gray-400 hover:text-gray-600">Скачать</a>
          <label class="text-[10px] text-gray-400 hover:text-gray-600 cursor-pointer">
            Заменить
            <input type="file" class="hidden" @change="$emit('upload', item, $event)" />
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

        <!-- Translation block -->
        <div v-if="item.review_status === 'needs_translation'"
          class="mt-2 p-2.5 bg-purple-50/60 rounded-lg border border-purple-100">
          <div class="flex items-center gap-2 text-xs text-purple-700">
            <span>Перевод: {{ item.translation_pages ?? '?' }} стр.</span>
            <span v-if="item.translation_price"> / {{ item.translation_price.toLocaleString() }} сум</span>
          </div>

          <!-- Upload translation -->
          <label v-if="item.status === 'needs_translation'"
            class="mt-2 inline-block cursor-pointer text-xs px-3 py-1.5 rounded-lg border border-purple-200 text-purple-700 bg-purple-100 hover:bg-purple-200 font-medium transition-colors">
            Загрузить перевод
            <input type="file" class="hidden" @change="$emit('upload-translation', item, $event)" />
          </label>

          <!-- Approve translation -->
          <div v-if="item.status === 'translated'" class="mt-2 flex items-center gap-2">
            <span class="text-xs text-purple-600">Перевод загружен</span>
            <button @click="$emit('approve-translation', item)"
              class="text-xs px-3 py-1.5 rounded-lg bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 font-medium transition-colors">
              Одобрить
            </button>
          </div>

          <p v-if="item.status === 'translation_approved'" class="mt-1 text-xs text-green-600 font-medium">
            Перевод одобрен
          </p>
        </div>
      </div>

      <!-- Right: actions -->
      <div class="shrink-0 flex items-center gap-1.5 flex-wrap justify-end">
        <!-- Checkbox toggle -->
        <button v-if="item.type === 'checkbox'" @click="$emit('toggle', item)"
          :class="['text-xs px-2.5 py-1 rounded-lg border font-medium transition-colors',
            item.is_checked ? 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100' : 'border-gray-200 text-gray-600 hover:bg-gray-50']">
          {{ item.is_checked ? 'Готово' : 'Отметить' }}
        </button>

        <!-- Upload (empty slot) -->
        <label v-if="!item.document && item.type !== 'checkbox' && !['approved','needs_translation','translated','translation_approved'].includes(item.status)"
          class="cursor-pointer text-xs px-2.5 py-1 rounded-lg border border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100 font-medium transition-colors">
          Загрузить
          <input type="file" class="hidden" @change="$emit('upload', item, $event)" />
        </label>

        <!-- Review buttons (manager) -->
        <template v-if="item.document && item.status === 'uploaded'">
          <button @click="$emit('review', item, 'approved')"
            class="text-xs px-2 py-1 rounded-lg bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 transition-colors">
            OK
          </button>
          <button @click="$emit('translation', item)"
            class="text-xs px-2 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition-colors">
            Перевод
          </button>
          <button @click="$emit('reject', item)"
            class="text-xs px-2 py-1 rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition-colors">
            Нет
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
import { computed } from 'vue';
import AppBadge from './AppBadge.vue';

const props = defineProps({ item: Object });
defineEmits(['upload', 'toggle', 'review', 'reject', 'translation', 'upload-translation', 'approve-translation', 'preview', 'delete', 'repeat']);

const STATUS_MAP = {
  approved:             { color: 'green',  label: 'Принято',     border: 'border-green-100 bg-green-50/30',   bar: 'bg-green-400' },
  translation_approved: { color: 'green',  label: 'Перевод OK',  border: 'border-green-100 bg-green-50/30',   bar: 'bg-green-400' },
  rejected:             { color: 'red',    label: 'Отклонено',   border: 'border-red-100 bg-red-50/20',       bar: 'bg-red-400' },
  needs_translation:    { color: 'purple', label: 'На перевод',  border: 'border-purple-100 bg-purple-50/20', bar: 'bg-purple-400' },
  translated:           { color: 'purple', label: 'Переведено',  border: 'border-purple-100 bg-purple-50/20', bar: 'bg-purple-400' },
  uploaded:             { color: 'blue',   label: 'На проверке', border: 'border-blue-100 bg-blue-50/20',     bar: 'bg-blue-400' },
};

const statusEntry = computed(() => {
  const s = props.item.status;
  if (STATUS_MAP[s]) return STATUS_MAP[s];
  if (props.item.is_checked) return { color: 'blue', label: 'На проверке', border: 'border-gray-100', bar: 'bg-blue-400' };
  return { color: 'gray', label: props.item.is_required ? 'Ожидает' : 'Не загружен', border: 'border-gray-100', bar: 'bg-gray-200' };
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
