<template>
  <div class="relative" ref="rootRef">
    <label v-if="label" class="text-sm font-medium text-gray-700 block mb-1">
      {{ label }} <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="relative">
      <input
        ref="inputRef"
        v-model="search"
        @input="onInput"
        @focus="open"
        @blur="onBlur"
        @keydown.down.prevent="moveDown"
        @keydown.up.prevent="moveUp"
        @keydown.enter.prevent="confirmHighlighted"
        @keydown.escape="close"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="[
          'w-full border rounded-lg px-3 text-sm outline-none pr-7 transition-colors',
          compact ? 'py-1.5' : 'py-2',
          disabled ? 'bg-gray-100 text-gray-400 cursor-not-allowed' :
          selected
            ? 'border-green-500 bg-green-50 text-green-800 font-medium'
            : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'
        ]"
      />
      <button v-if="selected" type="button" @click="clear"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-lg leading-none">
        &times;
      </button>
    </div>
    <div v-if="dropdownVisible && (filteredItems.length || allowAll)"
      class="absolute z-50 w-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg text-sm max-h-48 overflow-y-auto">
      <button v-if="allowAll" type="button"
        :class="['w-full text-left px-3 py-2 hover:bg-blue-50 text-gray-500', highlighted === -1 ? 'bg-blue-50' : '']"
        @mousedown.prevent="selectAll">
        {{ allLabel }}
      </button>
      <button v-for="(item, idx) in filteredItems" :key="item.value" type="button"
        :class="['w-full text-left px-3 py-2 hover:bg-blue-50 flex items-center gap-2', highlighted === idx ? 'bg-blue-50' : '']"
        @mousedown.prevent="selectItem(item)">
        <!-- Аватар или инициал -->
        <span v-if="item.avatar" class="w-5 h-5 rounded-full overflow-hidden shrink-0">
          <img :src="item.avatar" class="w-full h-full object-cover" />
        </span>
        <span v-else-if="showInitials" class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold shrink-0">
          {{ item.label?.[0]?.toUpperCase() ?? '?' }}
        </span>
        <span class="text-gray-900 truncate">{{ item.label }}</span>
        <span v-if="item.badge" class="ml-auto text-xs px-1.5 py-0.5 rounded-full" :class="item.badgeClass ?? 'bg-gray-100 text-gray-500'">
          {{ item.badge }}
        </span>
      </button>
      <div v-if="!filteredItems.length && !allowAll" class="px-3 py-2 text-gray-400 text-xs">
        {{ noResultsText }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
  modelValue:    { type: [String, Number], default: '' },
  items:         { type: Array, required: true },       // [{ value, label, avatar?, badge?, badgeClass? }]
  label:         { type: String, default: '' },
  placeholder:   { type: String, default: '' },
  required:      { type: Boolean, default: false },
  allowAll:      { type: Boolean, default: false },
  allLabel:      { type: String, default: '' },
  compact:       { type: Boolean, default: false },
  showInitials:  { type: Boolean, default: false },
  noResultsText: { type: String, default: 'Ничего не найдено' },
  disabled:      { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'change']);

const search = ref('');
const dropdownVisible = ref(false);
const highlighted = ref(-1);
const inputRef = ref(null);
const rootRef = ref(null);

const selected = computed(() => props.modelValue !== '' && props.modelValue !== null && props.modelValue !== undefined);

const filteredItems = computed(() => {
  const q = search.value.trim().toLowerCase();
  if (!q) return props.items;
  return props.items.filter(item =>
    item.label?.toLowerCase().includes(q)
  );
});

watch(() => props.modelValue, (val) => {
  if (val !== '' && val !== null && val !== undefined) {
    const found = props.items.find(i => i.value === val);
    search.value = found ? found.label : String(val);
  } else {
    search.value = '';
  }
}, { immediate: true });

// Пересинхронизация при изменении items (асинхронная загрузка)
watch(() => props.items, () => {
  if (props.modelValue !== '' && props.modelValue !== null) {
    const found = props.items.find(i => i.value === props.modelValue);
    if (found) search.value = found.label;
  }
});

function onInput() {
  if (props.modelValue !== '' && props.modelValue !== null) {
    emit('update:modelValue', '');
    emit('change', '');
  }
  dropdownVisible.value = true;
  highlighted.value = -1;
}

function open() {
  if (props.disabled) return;
  dropdownVisible.value = true;
}

function onBlur() {
  setTimeout(() => { dropdownVisible.value = false; }, 150);
}

function close() {
  dropdownVisible.value = false;
}

function selectItem(item) {
  emit('update:modelValue', item.value);
  emit('change', item.value);
  search.value = item.label;
  dropdownVisible.value = false;
}

function selectAll() {
  emit('update:modelValue', '');
  emit('change', '');
  search.value = '';
  dropdownVisible.value = false;
}

function clear() {
  emit('update:modelValue', '');
  emit('change', '');
  search.value = '';
  dropdownVisible.value = false;
  nextTick(() => inputRef.value?.focus());
}

function moveDown() {
  if (highlighted.value < filteredItems.value.length - 1) highlighted.value++;
}
function moveUp() {
  if (highlighted.value > (props.allowAll ? -1 : 0)) highlighted.value--;
}
function confirmHighlighted() {
  if (highlighted.value === -1 && props.allowAll) {
    selectAll();
  } else if (filteredItems.value[highlighted.value]) {
    selectItem(filteredItems.value[highlighted.value]);
  }
}
</script>
