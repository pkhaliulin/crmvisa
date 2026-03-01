<template>
  <div class="flex flex-col gap-1">
    <label v-if="label" :for="id" class="text-sm font-medium text-gray-700">
      {{ label }} <span v-if="required" class="text-red-500">*</span>
    </label>

    <div :class="[
      'flex items-center border rounded-lg transition-colors overflow-hidden',
      focused
        ? (hasError ? 'border-red-500 ring-1 ring-red-500' : 'border-blue-500 ring-1 ring-blue-500')
        : (hasError ? 'border-red-300' : 'border-gray-300'),
    ]">
      <!-- Фиксированный префикс +998 -->
      <span class="px-3 py-2 text-sm text-gray-500 bg-gray-50 border-r border-gray-200 select-none whitespace-nowrap font-mono">
        +998
      </span>

      <!-- Поле ввода -->
      <input
        :id="id"
        ref="inputRef"
        :value="displayValue"
        @input="onInput"
        @focus="focused = true"
        @blur="onBlur"
        @keydown="onKeydown"
        type="tel"
        inputmode="numeric"
        :placeholder="placeholder"
        :required="required"
        maxlength="12"
        class="flex-1 px-3 py-2 text-sm outline-none bg-white font-mono tracking-wider"
      />
    </div>

    <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="text-xs text-gray-400">{{ hint }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  label:      { type: String, default: '' },
  id:         { type: String, default: () => `phone-${Math.random().toString(36).slice(2)}` },
  error:      { type: String, default: '' },
  hint:       { type: String, default: 'Пример: 97 123 45 67' },
  required:   { type: Boolean, default: false },
  placeholder:{ type: String, default: '97 123 45 67' },
});

const emit = defineEmits(['update:modelValue']);

const focused  = ref(false);
const inputRef = ref(null);

const hasError = computed(() => !!props.error);

// Из modelValue (+998XXXXXXXXX или +998 XX XXX XX XX) извлекаем 9 цифр
const digitsFromModel = computed(() => {
  const raw = (props.modelValue || '').replace(/\D/g, '');
  // убираем 998 если есть в начале
  if (raw.startsWith('998')) return raw.slice(3);
  return raw;
});

// Форматируем 9 цифр в XX XXX XX XX
function formatDigits(digits) {
  const d = digits.replace(/\D/g, '').slice(0, 9);
  let result = '';
  if (d.length > 0) result += d.slice(0, 2);
  if (d.length > 2) result += ' ' + d.slice(2, 5);
  if (d.length > 5) result += ' ' + d.slice(5, 7);
  if (d.length > 7) result += ' ' + d.slice(7, 9);
  return result;
}

const displayValue = computed(() => formatDigits(digitsFromModel.value));

function onInput(e) {
  const raw     = e.target.value.replace(/\D/g, '').slice(0, 9);
  const formatted = formatDigits(raw);

  // Сохраняем позицию курсора (с учётом пробелов)
  const selStart = e.target.selectionStart;

  e.target.value = formatted;

  // Восстанавливаем курсор
  const newPos = Math.min(selStart, formatted.length);
  e.target.setSelectionRange(newPos, newPos);

  // Emit: +998XXXXXXXXX (без пробелов)
  const full = raw.length === 9 ? `+998${raw}` : raw ? `+998${raw}` : '';
  emit('update:modelValue', full);
}

function onBlur() {
  focused.value = false;
}

function onKeydown(e) {
  // Разрешить: цифры, Backspace, Delete, Tab, стрелки, Home, End
  const allowed = [
    'Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End',
  ];
  if (allowed.includes(e.key)) return;
  if (e.key >= '0' && e.key <= '9') return;
  // Блокируем остальное
  e.preventDefault();
}
</script>
