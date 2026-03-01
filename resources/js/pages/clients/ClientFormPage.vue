<template>
  <div class="max-w-xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">

      <!-- Header with back -->
      <div class="flex items-center gap-3 mb-6">
        <button @click="$router.back()"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <h2 class="text-lg font-bold text-gray-900">{{ isEdit ? 'Редактировать клиента' : 'Новый клиент' }}</h2>
      </div>

      <!-- Passport upload zone (only for new client) -->
      <div v-if="!isEdit" class="mb-6">
        <p class="text-sm font-medium text-gray-700 mb-2">
          Загрузить страницу паспорта
          <span class="text-xs font-normal text-gray-400 ml-1">— данные заполнятся автоматически</span>
        </p>

        <!-- Drop zone -->
        <label
          class="relative flex flex-col items-center justify-center border-2 border-dashed rounded-xl cursor-pointer transition-colors"
          :class="passportPreview
            ? 'border-green-300 bg-green-50 p-3'
            : dragOver
              ? 'border-blue-400 bg-blue-50 p-8'
              : 'border-gray-200 bg-gray-50 hover:border-blue-300 hover:bg-blue-50/40 p-8'"
          @dragover.prevent="dragOver = true"
          @dragleave="dragOver = false"
          @drop.prevent="onDrop">

          <!-- Preview after upload -->
          <template v-if="passportPreview">
            <div class="flex items-start gap-3 w-full">
              <img :src="passportPreview" alt="Паспорт" class="w-20 h-14 object-cover rounded-lg border border-gray-200 shrink-0" />
              <div class="flex-1 min-w-0">
                <p v-if="ocrLoading" class="text-sm text-blue-600 flex items-center gap-1.5">
                  <span class="inline-block w-3.5 h-3.5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></span>
                  Распознаём данные...
                </p>
                <p v-else-if="ocrDone" class="text-sm text-green-600 font-medium">Файл загружен</p>
                <p class="text-xs text-gray-400 mt-0.5 truncate">{{ passportFileName }}</p>
              </div>
              <button type="button" @click.prevent="clearPassport"
                class="text-gray-400 hover:text-red-500 transition-colors text-lg leading-none shrink-0">×</button>
            </div>
          </template>

          <!-- Empty state with example image -->
          <template v-else>
            <!-- Passport page example illustration -->
            <div class="w-44 h-28 mb-3 rounded-lg border border-gray-200 bg-white overflow-hidden shadow-sm relative select-none">
              <!-- Passport page mockup -->
              <div class="absolute inset-0 bg-gradient-to-br from-slate-50 to-slate-100 flex flex-col p-2 gap-1">
                <div class="flex gap-2">
                  <!-- Photo placeholder -->
                  <div class="w-10 h-12 bg-slate-200 rounded shrink-0 flex items-center justify-center">
                    <svg class="w-5 h-6 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 12c2.7 0 4-1.8 4-4s-1.3-4-4-4-4 1.8-4 4 1.3 4 4 4zm0 2c-2.7 0-8 1.3-8 4v1h16v-1c0-2.7-5.3-4-8-4z"/>
                    </svg>
                  </div>
                  <!-- Data lines -->
                  <div class="flex-1 flex flex-col gap-1 pt-0.5">
                    <div class="h-1.5 bg-slate-300 rounded w-full"></div>
                    <div class="h-1.5 bg-slate-300 rounded w-3/4"></div>
                    <div class="h-1.5 bg-slate-200 rounded w-full"></div>
                    <div class="h-1.5 bg-slate-200 rounded w-5/6"></div>
                    <div class="h-1.5 bg-slate-200 rounded w-4/5"></div>
                  </div>
                </div>
                <!-- MRZ lines -->
                <div class="mt-auto border-t border-dashed border-slate-300 pt-1">
                  <div class="h-1.5 bg-slate-300/60 rounded w-full font-mono"></div>
                  <div class="h-1.5 bg-slate-300/60 rounded w-full mt-0.5"></div>
                </div>
              </div>
              <!-- Arrow overlay -->
              <div class="absolute inset-0 flex items-center justify-center bg-blue-500/10 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </div>

            <svg class="w-6 h-6 text-gray-400 mb-1.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            <p class="text-sm text-gray-500 font-medium">Загрузить страницу с фото</p>
            <p class="text-xs text-gray-400 mt-0.5">JPG, PNG или PDF · до 10 МБ · перетащите сюда</p>
          </template>

          <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp,application/pdf"
            ref="passportInput" @change="onFileChange" />
        </label>

        <p v-if="ocrNote" class="text-xs text-amber-600 mt-1.5 flex items-center gap-1">
          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ ocrNote }}
        </p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">

        <AppInput
          v-model="form.name"
          label="ФИО"
          placeholder="Ислом Каримов"
          required
          :error="errors.name"
          :maxlength="120"
        />

        <!-- Phone — required, telegram hint -->
        <div>
          <AppPhoneInput
            v-model="form.phone"
            label="Телефон"
            :required="true"
            :error="errors.phone"
            @blur="validatePhone"
          />
          <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-blue-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.52 8.28l-1.92 9.12c-.12.6-.48.84-.96.6l-2.64-1.92-1.32 1.2c-.12.12-.36.24-.72.24l.24-2.76 4.8-4.44c.24-.24 0-.36-.36-.12L7.44 14.4l-2.64-.84c-.6-.12-.6-.6.12-.84l10.32-3.96c.48-.12.96.12.24.52z"/>
            </svg>
            Укажите номер телефона, на котором есть Telegram — уведомления о заявках будут приходить туда
          </p>
        </div>

        <AppInput
          v-model="form.email"
          label="Email"
          type="email"
          placeholder="client@mail.com"
          :error="errors.email"
          @blur="validateEmail"
        />

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Гражданство</label>
            <select v-model="form.nationality"
              :class="[
                'w-full border rounded-lg px-3 py-2 text-sm outline-none transition-colors',
                errors.nationality ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500',
              ]">
              <option value="">— не указано —</option>
              <option value="UZB">Узбекистан</option>
              <option value="KAZ">Казахстан</option>
              <option value="KGZ">Кыргызстан</option>
              <option value="TJK">Таджикистан</option>
              <option value="TKM">Туркменистан</option>
              <option value="RUS">Россия</option>
              <option value="UKR">Украина</option>
              <option value="AZE">Азербайджан</option>
              <option value="GEO">Грузия</option>
              <option value="ARM">Армения</option>
            </select>
            <p v-if="errors.nationality" class="text-xs text-red-600 mt-1">{{ errors.nationality }}</p>
          </div>
          <AppInput
            v-model="form.date_of_birth"
            label="Дата рождения"
            type="date"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <AppInput
            v-model="form.passport_number"
            label="Номер паспорта"
            placeholder="AA1234567"
            :maxlength="20"
          />
          <AppInput
            v-model="form.passport_expires_at"
            label="Срок действия"
            type="date"
          />
        </div>

        <AppSelect v-model="form.source" label="Источник" :options="sourceOptions" />

        <AppTextarea
          v-model="form.notes"
          label="Заметки"
          placeholder="Любая дополнительная информация о клиенте..."
          :maxlength="500"
          :rows="3"
        />

        <p v-if="errorMsg" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errorMsg }}</p>

        <div class="flex gap-3 pt-2">
          <AppButton type="submit" :loading="loading">
            {{ isEdit ? 'Сохранить' : 'Создать клиента' }}
          </AppButton>
          <RouterLink :to="{ name: 'clients' }">
            <AppButton type="button" variant="outline">Отмена</AppButton>
          </RouterLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { clientsApi } from '@/api/clients';
import AppInput from '@/components/AppInput.vue';
import AppPhoneInput from '@/components/AppPhoneInput.vue';
import AppTextarea from '@/components/AppTextarea.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppButton from '@/components/AppButton.vue';

const router = useRouter();
const route  = useRoute();
const isEdit = computed(() => !!route.params.id);

const form = reactive({
  name: '', phone: '', email: '', telegram_chat_id: '',
  nationality: '', date_of_birth: '', passport_number: '',
  passport_expires_at: '', source: 'direct', notes: '',
});
const errors   = ref({});
const errorMsg = ref('');
const loading  = ref(false);

// ── Passport upload ──────────────────────────────────────────────────────────
const passportInput   = ref(null);
const passportPreview = ref('');
const passportFileName = ref('');
const ocrLoading = ref(false);
const ocrDone    = ref(false);
const ocrNote    = ref('');
const dragOver   = ref(false);

function clearPassport() {
  passportPreview.value  = '';
  passportFileName.value = '';
  ocrLoading.value       = false;
  ocrDone.value          = false;
  ocrNote.value          = '';
  if (passportInput.value) passportInput.value.value = '';
}

async function handlePassportFile(file) {
  if (!file) return;
  passportFileName.value = file.name;
  passportPreview.value  = URL.createObjectURL(file);
  ocrLoading.value       = true;
  ocrDone.value          = false;
  ocrNote.value          = '';

  try {
    const { data } = await clientsApi.parsePassport(file);
    const d = data.data ?? {};

    // Заполняем поля если OCR вернул данные
    if (d.name)                form.name                = d.name;
    if (d.date_of_birth)       form.date_of_birth       = d.date_of_birth;
    if (d.passport_number)     form.passport_number     = d.passport_number;
    if (d.passport_expires_at) form.passport_expires_at = d.passport_expires_at;
    if (d.nationality)         form.nationality         = d.nationality;

    ocrNote.value = data.message ?? '';
  } catch {
    ocrNote.value = 'Не удалось загрузить файл. Заполните данные вручную.';
  } finally {
    ocrLoading.value = false;
    ocrDone.value    = true;
  }
}

function onFileChange(e) {
  handlePassportFile(e.target.files[0]);
}

function onDrop(e) {
  dragOver.value = false;
  handlePassportFile(e.dataTransfer?.files[0]);
}

// ── Sources ──────────────────────────────────────────────────────────────────
const sourceOptions = [
  { value: 'direct',      label: 'Прямой (агентство)' },
  { value: 'referral',    label: 'Реферал' },
  { value: 'marketplace', label: 'Маркетплейс CRMBOR' },
  { value: 'other',       label: 'Другое' },
];

// ── Validation ───────────────────────────────────────────────────────────────
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
}
function isValidPhone(phone) {
  return phone.replace(/\D/g, '').length >= 7;
}
function validateEmail() {
  if (form.email && !isValidEmail(form.email)) {
    errors.value.email = 'Введите корректный email';
  } else {
    delete errors.value.email;
  }
}
function validatePhone() {
  if (form.phone && !isValidPhone(form.phone)) {
    errors.value.phone = 'Введите корректный номер телефона';
  } else {
    delete errors.value.phone;
  }
}

onMounted(async () => {
  if (isEdit.value) {
    const { data } = await clientsApi.get(route.params.id);
    Object.assign(form, data.data);
  }
});

async function handleSubmit() {
  errors.value   = {};
  errorMsg.value = '';

  if (!form.phone) {
    errors.value.phone = 'Телефон обязателен';
    return;
  }
  if (form.email && !isValidEmail(form.email)) {
    errors.value.email = 'Введите корректный email';
    return;
  }

  loading.value = true;
  try {
    if (isEdit.value) {
      await clientsApi.update(route.params.id, form);
      router.push({ name: 'clients.show', params: { id: route.params.id } });
    } else {
      const { data } = await clientsApi.create(form);
      router.push({ name: 'clients.show', params: { id: data.data.id } });
    }
  } catch (err) {
    const d = err.response?.data;
    if (d?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(d.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    } else {
      errorMsg.value = d?.message || 'Ошибка сохранения';
    }
  } finally {
    loading.value = false;
  }
}
</script>
