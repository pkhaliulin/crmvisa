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
        <h2 class="text-lg font-bold text-gray-900">{{ isEdit ? t('crm.clientForm.editTitle') : t('crm.clientForm.newTitle') }}</h2>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">

        <AppInput
          v-model="form.name"
          :label="t('crm.clientForm.name')"
          :placeholder="t('crm.clientForm.namePlaceholder')"
          required
          :error="errors.name"
          :maxlength="120"
          @blur="form.name = titleCase(form.name)"
        />

        <!-- Phone — required, telegram hint -->
        <div>
          <AppPhoneInput
            v-model="form.phone"
            :label="t('crm.clientForm.phone')"
            :required="true"
            :error="errors.phone"
            @blur="validatePhone"
          />
          <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-blue-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.52 8.28l-1.92 9.12c-.12.6-.48.84-.96.6l-2.64-1.92-1.32 1.2c-.12.12-.36.24-.72.24l.24-2.76 4.8-4.44c.24-.24 0-.36-.36-.12L7.44 14.4l-2.64-.84c-.6-.12-.6-.6.12-.84l10.32-3.96c.48-.12.96.12.24.52z"/>
            </svg>
            {{ t('crm.clientForm.phoneTelegram') }}
          </p>
        </div>

        <AppInput
          v-model="form.email"
          :label="t('crm.clientForm.email')"
          type="email"
          :placeholder="t('crm.clientForm.emailPlaceholder')"
          :error="errors.email"
          @blur="validateEmail"
        />

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.clientForm.nationality') }}</label>
            <select v-model="form.nationality"
              :class="[
                'w-full border rounded-lg px-3 py-2 text-sm outline-none transition-colors',
                errors.nationality ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500',
              ]">
              <option value="">{{ t('crm.clientForm.nationalityDefault') }}</option>
              <option value="UZB">{{ t('crm.nationalities.UZB') }}</option>
              <option value="KAZ">{{ t('crm.nationalities.KAZ') }}</option>
              <option value="KGZ">{{ t('crm.nationalities.KGZ') }}</option>
              <option value="TJK">{{ t('crm.nationalities.TJK') }}</option>
              <option value="TKM">{{ t('crm.nationalities.TKM') }}</option>
              <option value="RUS">{{ t('crm.nationalities.RUS') }}</option>
              <option value="UKR">{{ t('crm.nationalities.UKR') }}</option>
              <option value="AZE">{{ t('crm.nationalities.AZE') }}</option>
              <option value="GEO">{{ t('crm.nationalities.GEO') }}</option>
              <option value="ARM">{{ t('crm.nationalities.ARM') }}</option>
            </select>
            <p v-if="errors.nationality" class="text-xs text-red-600 mt-1">{{ errors.nationality }}</p>
          </div>
          <AppInput
            v-model="form.date_of_birth"
            :label="t('crm.clientForm.dob')"
            type="date"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <AppInput
            v-model="form.passport_number"
            :label="t('crm.clientForm.passport')"
            :placeholder="t('crm.clientForm.passportPlaceholder')"
            :maxlength="20"
            @blur="form.passport_number = (form.passport_number || '').toUpperCase()"
          />
          <AppInput
            v-model="form.passport_expires_at"
            :label="t('crm.clientForm.passportExpiry')"
            type="date"
          />
        </div>

        <AppSelect v-model="form.source" :label="t('crm.clientForm.source')" :options="sourceOptions" />

        <AppTextarea
          v-model="form.notes"
          :label="t('crm.clientForm.notesLabel')"
          :placeholder="t('crm.clientForm.notesPlaceholder')"
          :maxlength="500"
          :rows="3"
        />

        <p v-if="errorMsg" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errorMsg }}</p>

        <div class="flex gap-3 pt-2">
          <AppButton type="submit" :loading="loading">
            {{ isEdit ? t('crm.clientForm.save') : t('crm.clientForm.create') }}
          </AppButton>
          <RouterLink :to="{ name: 'clients' }">
            <AppButton type="button" variant="outline">{{ t('crm.clientForm.cancel') }}</AppButton>
          </RouterLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { clientsApi } from '@/api/clients';
import { useReferences } from '@/composables/useReferences';
import AppInput from '@/components/AppInput.vue';
import AppPhoneInput from '@/components/AppPhoneInput.vue';
import AppTextarea from '@/components/AppTextarea.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppButton from '@/components/AppButton.vue';
import { titleCase } from '@/utils/format';

const { t } = useI18n();
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

// ── Sources (из справочника) ─────────────────────────────────────────────────
const { activeItems } = useReferences();
const sourceOptions = computed(() =>
  activeItems('lead_source').map(i => ({ value: i.code, label: i.label_ru }))
);

// ── Validation ───────────────────────────────────────────────────────────────
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
}
function isValidPhone(phone) {
  return phone.replace(/\D/g, '').length >= 7;
}
function validateEmail() {
  if (form.email && !isValidEmail(form.email)) {
    errors.value.email = t('crm.clientForm.invalidEmail');
  } else {
    delete errors.value.email;
  }
}
function validatePhone() {
  if (form.phone && !isValidPhone(form.phone)) {
    errors.value.phone = t('crm.clientForm.invalidPhone');
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
    errors.value.phone = t('crm.clientForm.phoneRequired');
    return;
  }
  if (form.email && !isValidEmail(form.email)) {
    errors.value.email = t('crm.clientForm.invalidEmail');
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
      errorMsg.value = d?.message || t('crm.clientForm.saveError');
    }
  } finally {
    loading.value = false;
  }
}
</script>
