<template>
  <div v-if="loading" class="flex items-center justify-center py-32">
    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
  </div>

  <div v-else-if="client" class="space-y-6 max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <!-- Title row with back button -->
      <div class="flex items-center gap-3 mb-4">
        <button @click="$router.back()"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <div class="flex-1 min-w-0">
          <h2 class="text-xl font-bold text-gray-900 truncate">{{ client.name }}</h2>
          <p class="text-sm text-gray-500">
            <a v-if="client.phone" :href="`tel:${client.phone}`" class="hover:text-blue-600">{{ formatPhone(client.phone) }}</a><span v-if="client.email"> · {{ client.email }}</span>
          </p>
        </div>
        <div class="flex items-center gap-2">
          <AppButton v-if="hasAiData" variant="outline" size="sm" :loading="applyingAi" @click="applyAiData">
            {{ t('crm.clientDetail.fillFromDocs') }}
          </AppButton>
          <RouterLink :to="{ name: 'clients.create', query: { edit: client.id } }">
            <AppButton variant="outline" size="sm">{{ t('crm.clientDetail.edit') }}</AppButton>
          </RouterLink>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6 pt-4 border-t">
        <div>
          <p class="text-xs text-gray-400 uppercase">{{ t('crm.clientDetail.nationality') }}</p>
          <p class="text-sm font-medium mt-1">{{ client.nationality ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase">{{ t('crm.clientDetail.dob') }}</p>
          <p class="text-sm font-medium mt-1">{{ client.date_of_birth ? formatDate(client.date_of_birth) : '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase">{{ t('crm.clientDetail.passport') }}</p>
          <p class="text-sm font-medium mt-1">{{ client.passport_number ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase">{{ t('crm.clientDetail.validUntil') }}</p>
          <p :class="['text-sm font-medium mt-1', passportClass]">
            {{ client.passport_expires_at ? formatDate(client.passport_expires_at) : '—' }}
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase">{{ t('crm.clientDetail.source') }}</p>
          <AppBadge :color="sourceColor" class="mt-1">{{ sourceLabel }}</AppBadge>
        </div>
      </div>

      <!-- AI fill success toast -->
      <div v-if="aiAppliedDocs.length" class="mt-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-xs">
        {{ t('crm.clientDetail.aiAppliedFrom') }}: {{ aiAppliedDocs.join(', ') }}
      </div>
    </div>

    <!-- Profile (questionnaire answers) -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">{{ t('crm.clientDetail.profileTitle') }}</h3>
      </div>

      <template v-if="profile">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

          <!-- Family -->
          <div class="space-y-2">
            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">{{ t('crm.clientDetail.profileFamily') }}</p>
            <div class="space-y-1">
              <div class="flex justify-between text-xs">
                <span class="text-gray-500">{{ t('crm.clientDetail.profileMarital') }}</span>
                <span class="font-medium text-gray-800">{{ maritalLabel }}</span>
              </div>
              <div v-if="profile.marital_status === 'married'" class="flex justify-between text-xs">
                <span class="text-gray-500">{{ t('crm.clientDetail.profileSpouseWorks') }}</span>
                <span class="font-medium" :class="profile.spouse_employed ? 'text-green-600' : 'text-gray-400'">{{ profile.spouse_employed ? t('crm.clientDetail.profileYes') : t('crm.clientDetail.profileNo') }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-gray-500">{{ t('crm.clientDetail.profileChildren') }}</span>
                <span class="font-medium text-gray-800">{{ profile.children_count ?? 0 }}</span>
              </div>
            </div>
          </div>

          <!-- Employment -->
          <div class="space-y-2">
            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">{{ t('crm.clientDetail.profileWork') }}</p>
            <div class="space-y-1">
              <div class="flex justify-between text-xs">
                <span class="text-gray-500">{{ t('crm.clientDetail.profileEmployment') }}</span>
                <span class="font-medium text-gray-800">{{ employmentLabel }}</span>
              </div>
              <div v-if="profile.position" class="flex justify-between text-xs">
                <span class="text-gray-500">{{ t('crm.clientDetail.profilePosition') }}</span>
                <span class="font-medium text-gray-800">{{ profile.position }}</span>
              </div>
              <div v-if="profile.employer_name" class="flex justify-between text-xs">
                <span class="text-gray-500">{{ t('crm.clientDetail.profileEmployer') }}</span>
                <span class="font-medium text-gray-800">{{ profile.employer_name }}</span>
              </div>
              <div v-if="profile.years_at_current_job" class="flex justify-between text-xs">
                <span class="text-gray-500">{{ t('crm.clientDetail.profileYearsJob') }}</span>
                <span class="font-medium text-gray-800">{{ profile.years_at_current_job }}</span>
              </div>
            </div>
          </div>

          <!-- Finance -->
          <div class="space-y-2">
            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">{{ t('crm.clientDetail.profileFinance') }}</p>
            <div class="space-y-1">
              <div v-if="profile.monthly_income" class="flex justify-between text-xs">
                <span class="text-gray-500">{{ t('crm.clientDetail.profileIncome') }}</span>
                <span class="font-medium text-gray-800">${{ fmtMoney(profile.monthly_income) }}</span>
              </div>
              <div v-if="profile.bank_balance" class="flex justify-between text-xs">
                <span class="text-gray-500">{{ t('crm.clientDetail.profileBank') }}</span>
                <span class="font-medium text-gray-800">${{ fmtMoney(profile.bank_balance) }}</span>
              </div>
              <div v-if="profile.has_fixed_deposit && profile.fixed_deposit_amount" class="flex justify-between text-xs">
                <span class="text-gray-500">{{ t('crm.clientDetail.profileDeposit') }}</span>
                <span class="font-medium text-gray-800">${{ fmtMoney(profile.fixed_deposit_amount) }}</span>
              </div>
              <p v-if="!profile.monthly_income && !profile.bank_balance" class="text-xs text-gray-400">{{ t('crm.clientDetail.profileNoData') }}</p>
            </div>
          </div>

          <!-- Assets -->
          <div class="space-y-2">
            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">{{ t('crm.clientDetail.profileAssets') }}</p>
            <div class="flex flex-wrap gap-1.5">
              <span v-if="profile.has_real_estate" class="text-[10px] px-2 py-0.5 rounded-full bg-green-50 text-green-700 font-medium">{{ t('crm.clientDetail.profileRealEstate') }}</span>
              <span v-if="profile.has_car" class="text-[10px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-medium">{{ t('crm.clientDetail.profileCar') }}</span>
              <span v-if="profile.has_business" class="text-[10px] px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 font-medium">{{ t('crm.clientDetail.profileBusiness') }}</span>
              <span v-if="!profile.has_real_estate && !profile.has_car && !profile.has_business" class="text-xs text-gray-400">{{ t('crm.clientDetail.profileNoAssets') }}</span>
            </div>
          </div>

          <!-- Visa History -->
          <div class="space-y-2">
            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">{{ t('crm.clientDetail.profileVisaHistory') }}</p>
            <div class="space-y-1">
              <div class="flex flex-wrap gap-1.5">
                <span v-if="profile.has_schengen_visa" class="text-[10px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-medium">Schengen</span>
                <span v-if="profile.has_us_visa" class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-medium">USA</span>
                <span v-if="profile.has_uk_visa" class="text-[10px] px-2 py-0.5 rounded-full bg-red-50 text-red-700 font-medium">UK</span>
                <span v-if="!profile.has_schengen_visa && !profile.has_us_visa && !profile.has_uk_visa" class="text-xs text-gray-400">{{ t('crm.clientDetail.profileNoVisas') }}</span>
              </div>
              <div v-if="profile.previous_refusals > 0" class="text-xs font-semibold text-red-500">{{ t('crm.clientDetail.profileRefusals', { n: profile.previous_refusals }) }}</div>
              <div v-if="profile.has_overstay" class="text-xs font-semibold text-red-600">{{ t('crm.clientDetail.profileOverstay') }}</div>
            </div>
          </div>

          <!-- Education -->
          <div class="space-y-2">
            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">{{ t('crm.clientDetail.profileEducation') }}</p>
            <p class="text-xs font-medium text-gray-800">{{ educationLabel }}</p>
          </div>
        </div>
      </template>

      <div v-else class="text-center py-6">
        <p class="text-sm text-gray-400 mb-3">{{ t('crm.clientDetail.profileEmpty') }}</p>
        <AppButton v-if="hasAiData" variant="outline" size="sm" :loading="applyingAi" @click="applyAiData">
          {{ t('crm.clientDetail.fillFromDocs') }}
        </AppButton>
      </div>
    </div>

    <!-- Cases -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">{{ t('crm.clientDetail.casesCount', { n: client.cases?.length ?? 0 }) }}</h3>
        <RouterLink :to="{ name: 'cases.create', query: { client_id: client.id, client_label: `${client.name} — ${client.phone}` } }">
          <AppButton size="sm">{{ t('crm.clientDetail.newCase') }}</AppButton>
        </RouterLink>
      </div>

      <div v-if="client.cases?.length" class="space-y-3">
        <div v-for="c in client.cases" :key="c.id"
          class="group border border-gray-200 rounded-xl overflow-hidden hover:border-blue-300 hover:shadow-sm transition-all cursor-pointer border-l-4"
          :class="urgencyBorder(c)"
          @click="$router.push({ name: 'cases.show', params: { id: c.id } })">

          <!-- Top row -->
          <div class="px-4 pt-3 pb-2 flex items-start justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
              <span class="text-xl leading-none shrink-0">{{ countryFlag(c.country_code) }}</span>
              <div class="min-w-0">
                <p class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors text-sm leading-tight truncate">
                  {{ countryName(c.country_code) }}
                  <span class="text-gray-400 font-normal"> — {{ visaTypeName(c.visa_type) }}</span>
                </p>
              </div>
            </div>
            <span class="shrink-0 text-xs font-bold px-2 py-0.5 rounded-full" :class="priorityChip(c.priority)">
              {{ PRIORITY_LABELS[c.priority] ?? c.priority }}
            </span>
          </div>

          <!-- Stats bar -->
          <div class="border-t border-gray-100 bg-gray-50/60 px-4 py-2 flex flex-wrap items-center gap-x-4 gap-y-1">
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full shrink-0" :class="stageDot(c.stage)"></span>
              <span class="text-xs font-semibold text-gray-600">{{ STAGE_LABELS[c.stage] ?? c.stage }}</span>
            </div>
            <div class="flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span v-if="c.critical_date" class="text-xs font-semibold" :class="deadlineText(c)">
                {{ formatDate(c.critical_date) }}
              </span>
              <span v-else class="text-xs text-gray-400">{{ t('crm.clientDetail.noDeadline') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              <span class="text-xs text-gray-500">{{ c.assignee?.name ?? t('crm.clientDetail.noManager') }}</span>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400">{{ t('crm.clientDetail.noCases') }}</p>
    </div>

    <!-- VisaBor scoring (from client's personal cabinet) -->
    <div v-if="visaborScores.length" class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="font-semibold text-gray-800">{{ t('crm.clientDetail.visaborScoring') }}</h3>
          <p class="text-xs text-gray-400 mt-0.5">{{ t('crm.clientDetail.visaborScoringDesc') }}</p>
        </div>
        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold">VisaBor</span>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        <div v-for="vs in visaborScores" :key="vs.country_code" class="border border-gray-100 rounded-lg p-3">
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm font-semibold">{{ countryFlag(vs.country_code) }} {{ countryName(vs.country_code) }}</span>
            <span :class="['text-lg font-bold', scoreColor(vs.score)]">{{ vs.score }}</span>
          </div>
          <div class="bg-gray-200 rounded-full h-1.5 overflow-hidden">
            <div :class="['h-full rounded-full', scoreBarColor(vs.score)]" :style="{ width: `${vs.score}%` }" />
          </div>
          <!-- Breakdown blocks -->
          <div v-if="vs.breakdown" class="mt-2 space-y-0.5">
            <div v-for="(val, key) in vs.breakdown" :key="key" class="flex items-center gap-1.5">
              <span class="text-[10px] text-gray-400 w-16 truncate">{{ visaborBlockLabel(key) }}</span>
              <div class="flex-1 h-1 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-emerald-400" :style="{ width: `${val}%` }" />
              </div>
              <span class="text-[10px] text-gray-500 w-5 text-right">{{ val }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Scoring (agency) -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">{{ t('crm.clientDetail.scoring') }}</h3>
        <AppButton variant="outline" size="sm" :loading="recalcLoading" @click="recalculate">
          {{ t('crm.clientDetail.recalculate') }}
        </AppButton>
      </div>

      <div v-if="scores.length" class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <div v-for="s in scores" :key="s.country_code"
          class="border rounded-lg p-3"
          :class="s.is_blocked ? 'border-red-200 bg-red-50' : 'border-gray-100'"
        >
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm font-semibold">{{ countryFlag(s.country_code) }} {{ s.country_code }}</span>
            <span :class="['text-lg font-bold', scoreColor(s.score)]">{{ s.score }}</span>
          </div>
          <div class="bg-gray-200 rounded-full h-1.5 overflow-hidden">
            <div :class="['h-full rounded-full', scoreBarColor(s.score)]" :style="{ width: `${s.score}%` }" />
          </div>
          <p class="text-xs text-gray-500 mt-1">{{ s.level_label }}</p>

          <!-- Block scores breakdown -->
          <div v-if="s.block_scores" class="mt-2 space-y-0.5">
            <div v-for="(bs, code) in s.block_scores" :key="code" class="flex items-center gap-1.5">
              <span class="text-[10px] text-gray-400 w-6">{{ code }}</span>
              <div class="flex-1 h-1 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-blue-400" :style="{ width: `${bs}%` }" />
              </div>
              <span class="text-[10px] text-gray-500 w-5 text-right">{{ bs }}</span>
            </div>
          </div>

          <!-- Weak blocks -->
          <div v-if="s.weak_blocks?.length" class="mt-1.5 flex flex-wrap gap-1">
            <span v-for="wb in s.weak_blocks" :key="wb" class="text-[10px] px-1.5 py-0.5 rounded bg-red-50 text-red-600 font-medium">{{ wb }}</span>
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400">{{ t('crm.clientDetail.scoringEmpty') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { clientsApi } from '@/api/clients';
import { useCountries } from '@/composables/useCountries';
import { useReferences } from '@/composables/useReferences';
import AppBadge from '@/components/AppBadge.vue';
import AppButton from '@/components/AppButton.vue';
import { formatPhone, formatDate } from '@/utils/format';

const { t } = useI18n();
const { countryName, countryFlag, visaTypeName } = useCountries();
const { label: refLabel } = useReferences();

const route  = useRoute();
const id     = route.params.id;
const client = ref(null);
const profile = ref(null);
const scores = ref([]);
const visaborScores = ref([]);
const loading = ref(true);
const recalcLoading = ref(false);
const applyingAi = ref(false);
const aiAppliedDocs = ref([]);

// Has at least 1 case (potential AI data source)
const hasAiData = computed(() => (client.value?.cases?.length ?? 0) > 0);

const STAGE_LABELS = computed(() => ({
  lead: t('crm.stages.lead'),
  qualification: t('crm.stages.qualification'),
  documents: t('crm.stages.documents'),
  doc_review: t('crm.stages.doc_review'),
  translation: t('crm.stages.translation'),
  appointment: t('crm.stages.appointment'),
  review: t('crm.stages.review'),
  result: t('crm.stages.result'),
}));

const PRIORITY_LABELS = computed(() => ({
  low: t('crm.priority.low'),
  normal: t('crm.priority.normal'),
  high: t('crm.priority.high'),
  urgent: t('crm.priority.urgent'),
}));

const STAGE_DOTS = {
  lead: 'bg-gray-400', qualification: 'bg-blue-500', documents: 'bg-purple-500',
  translation: 'bg-yellow-500', appointment: 'bg-orange-500', review: 'bg-indigo-500', result: 'bg-green-500',
};
const stageDot = (s) => STAGE_DOTS[s] ?? 'bg-gray-400';

function daysUntil(c) {
  if (!c.critical_date || c.stage === 'result') return null;
  return Math.floor((new Date(c.critical_date) - new Date()) / 86400000);
}
function urgencyBorder(c) {
  if (c.stage === 'result') return 'border-l-green-400';
  const d = daysUntil(c);
  if (d === null) return 'border-l-gray-200';
  if (d < 0)  return 'border-l-red-500';
  if (d <= 5) return 'border-l-yellow-400';
  return 'border-l-blue-300';
}
function deadlineText(c) {
  const d = daysUntil(c);
  if (d === null) return 'text-gray-400';
  if (d < 0)  return 'text-red-600';
  if (d <= 5) return 'text-yellow-600';
  return 'text-gray-600';
}
function priorityChip(p) {
  return { urgent: 'bg-red-100 text-red-700', high: 'bg-orange-100 text-orange-700', normal: 'bg-blue-100 text-blue-700', low: 'bg-gray-100 text-gray-500' }[p] ?? 'bg-gray-100 text-gray-500';
}


const SOURCE_COLORS = {
  direct: 'blue', referral: 'purple', marketplace: 'green',
  website: 'cyan', social_media: 'pink', partner: 'indigo',
  repeat: 'teal', other: 'gray',
};
const sourceLabel = computed(() => refLabel('lead_source', client.value?.source) || '');
const sourceColor = computed(() => SOURCE_COLORS[client.value?.source] ?? 'gray');

const passportClass = computed(() => {
  const d = client.value?.passport_expires_at;
  if (!d) return '';
  const days = Math.floor((new Date(d) - new Date()) / 86400000);
  return days < 0 ? 'text-red-600 font-bold' : days <= 90 ? 'text-yellow-600' : 'text-gray-700';
});

// Profile computed labels
const MARITAL_MAP = { married: 'profileMarried', single: 'profileSingle', divorced: 'profileDivorced', widowed: 'profileWidowed' };
const maritalLabel = computed(() => {
  const k = MARITAL_MAP[profile.value?.marital_status];
  return k ? t(`crm.clientDetail.${k}`) : '—';
});

const EMPLOYMENT_MAP = { government: 'profileGovernment', private: 'profilePrivate', business_owner: 'profileBusinessOwner', self_employed: 'profileSelfEmployed', student: 'profileStudent', retired: 'profileRetired', unemployed: 'profileUnemployed' };
const employmentLabel = computed(() => {
  const k = EMPLOYMENT_MAP[profile.value?.employment_type];
  return k ? t(`crm.clientDetail.${k}`) : '—';
});

const EDUCATION_MAP = { phd: 'profilePhd', master: 'profileMaster', bachelor: 'profileBachelor', secondary: 'profileSecondary', none: 'profileNoEducation' };
const educationLabel = computed(() => {
  const k = EDUCATION_MAP[profile.value?.education_level];
  return k ? t(`crm.clientDetail.${k}`) : '—';
});

function fmtMoney(v) { return v ? Number(v).toLocaleString('en-US') : '0'; }

const VISABOR_BLOCK_LABELS = { finances: 'finances', social_ties: 'social_ties', visa_history: 'visa_history', travel_purpose: 'travel_purpose', profile: 'profile' };
function visaborBlockLabel(key) {
  return t(`crm.clientDetail.vb_${key}`, key);
}

function scoreColor(s) {
  if (s >= 80) return 'text-green-600';
  if (s >= 60) return 'text-yellow-600';
  return 'text-red-600';
}
function scoreBarColor(s) {
  if (s >= 80) return 'bg-green-500';
  if (s >= 60) return 'bg-yellow-400';
  return 'bg-red-400';
}

async function load() {
  loading.value = true;
  try {
    const [cRes, sRes, pRes] = await Promise.all([
      clientsApi.get(id),
      clientsApi.getScores(id),
      clientsApi.getProfile(id),
    ]);
    client.value = cRes.data.data;
    scores.value = sRes.data.data ?? [];
    profile.value = pRes.data.data ?? null;
    // Profile may be empty object with only id/client_id
    if (profile.value && !profile.value.marital_status && !profile.value.employment_type && !profile.value.bank_balance && !profile.value.has_real_estate) {
      profile.value = null;
    }
    // Auto-sync from VisaBor if client has portal account and profile is empty
    if (client.value?.public_user_id) {
      if (!profile.value) {
        // Profile empty — auto-pull data from VisaBor portal
        try {
          const { data } = await clientsApi.applyAiData(id);
          client.value = data.data.client;
          if (data.data.profile) profile.value = data.data.profile;
          aiAppliedDocs.value = data.data.applied_from ?? [];
          if (data.data.visabor_scoring?.length) visaborScores.value = data.data.visabor_scoring;
          // Recalculate agency scoring with new profile data
          if (profile.value) {
            const sRes = await clientsApi.recalculate(id);
            scores.value = sRes.data.data ?? [];
          }
        } catch { /* sync failed, not critical */ }
      } else {
        // Profile exists, just load VisaBor scoring
        try {
          const vRes = await clientsApi.visaborScoring(id);
          visaborScores.value = vRes.data.data ?? [];
        } catch { /* no portal scores */ }
      }
    }
  } finally {
    loading.value = false;
  }
}

async function recalculate() {
  recalcLoading.value = true;
  try {
    const { data } = await clientsApi.recalculate(id);
    scores.value = data.data ?? [];
  } finally {
    recalcLoading.value = false;
  }
}

async function applyAiData() {
  applyingAi.value = true;
  aiAppliedDocs.value = [];
  try {
    const { data } = await clientsApi.applyAiData(id);
    client.value = data.data.client;
    if (data.data.profile) profile.value = data.data.profile;
    aiAppliedDocs.value = data.data.applied_from ?? [];
    if (data.data.visabor_scoring?.length) visaborScores.value = data.data.visabor_scoring;
    // Recalculate scoring after profile update
    if (profile.value) {
      const sRes = await clientsApi.recalculate(id);
      scores.value = sRes.data.data ?? [];
    }
  } finally {
    applyingAi.value = false;
  }
}

onMounted(load);
</script>
