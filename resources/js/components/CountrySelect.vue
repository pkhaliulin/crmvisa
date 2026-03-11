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
        :class="[
          'w-full border rounded-lg px-3 py-2 text-sm outline-none pr-7 transition-colors',
          inputSizeClass,
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
    <div v-if="dropdownVisible && filtered.length"
      class="absolute z-50 w-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg text-sm max-h-48 overflow-y-auto">
      <button v-if="allowAll" type="button"
        :class="['w-full text-left px-3 py-2 hover:bg-blue-50 text-gray-500', highlighted === -1 ? 'bg-blue-50' : '']"
        @mousedown.prevent="selectAll">
        {{ allLabel }}
      </button>
      <button v-for="(c, idx) in filtered" :key="c.code" type="button"
        :class="['w-full text-left px-3 py-2 hover:bg-blue-50 flex items-center gap-2', highlighted === idx ? 'bg-blue-50' : '']"
        @mousedown.prevent="select(c)">
        <span class="text-base">{{ c.flag }}</span>
        <span class="text-gray-900">{{ c.name }}</span>
        <span v-if="c.extra" class="ml-1 text-xs text-gray-400">({{ c.extra }})</span>
        <span class="ml-auto font-mono text-xs text-gray-400">{{ c.code }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
  modelValue:  { type: String, default: '' },
  countries:   { type: Array, required: true },
  label:       { type: String, default: '' },
  placeholder: { type: String, default: '' },
  required:    { type: Boolean, default: false },
  allowAll:    { type: Boolean, default: false },
  allLabel:    { type: String, default: '' },
  compact:     { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'change']);

const search = ref('');
const dropdownVisible = ref(false);
const highlighted = ref(-1);
const inputRef = ref(null);
const rootRef = ref(null);

const inputSizeClass = computed(() => props.compact ? 'py-1.5 text-sm' : 'py-2 text-sm');

// Нормализуем формат стран: поддерживаем { code, name, flag } и { country_code, name, flag_emoji }
const normalized = computed(() =>
  props.countries.map(c => ({
    code:  c.code ?? c.country_code ?? '',
    name:  c.name ?? '',
    flag:  c.flag ?? c.flag_emoji ?? flagFromCode(c.code ?? c.country_code ?? ''),
    extra: c.extra ?? c.agencies_count ?? null,
    raw:   c,
  }))
);

function flagFromCode(code) {
  if (!code || code.length !== 2) return '';
  return code.toUpperCase().split('').map(c =>
    String.fromCodePoint(c.charCodeAt(0) - 65 + 0x1F1E6)
  ).join('');
}

const selected = computed(() => !!props.modelValue);

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase();
  if (!q) return normalized.value;
  return normalized.value.filter(c =>
    c.name.toLowerCase().includes(q) ||
    c.code.toLowerCase().startsWith(q)
  );
});

// Синхронизация: при внешнем изменении modelValue -> обновить search
watch(() => props.modelValue, (val) => {
  if (val) {
    const found = normalized.value.find(c => c.code === val);
    search.value = found ? found.name : val;
  } else {
    search.value = '';
  }
}, { immediate: true });

function onInput() {
  if (props.modelValue) {
    emit('update:modelValue', '');
    emit('change', '');
  }
  dropdownVisible.value = true;
  highlighted.value = -1;
}

function open() {
  dropdownVisible.value = true;
}

function onBlur() {
  setTimeout(() => { dropdownVisible.value = false; }, 150);
}

function close() {
  dropdownVisible.value = false;
}

function select(c) {
  emit('update:modelValue', c.code);
  emit('change', c.code);
  search.value = c.name;
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
  if (highlighted.value < filtered.value.length - 1) highlighted.value++;
}
function moveUp() {
  if (highlighted.value > (props.allowAll ? -1 : 0)) highlighted.value--;
}
function confirmHighlighted() {
  if (highlighted.value === -1 && props.allowAll) {
    selectAll();
  } else if (filtered.value[highlighted.value]) {
    select(filtered.value[highlighted.value]);
  }
}
</script>
