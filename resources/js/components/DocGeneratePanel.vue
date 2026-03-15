<template>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <button @click="open = !open"
            class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 bg-indigo-50 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <span class="font-semibold text-[#0A1F44] text-sm">{{ t('crm.docGen.title') }}</span>
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div v-if="open" class="px-4 pb-4 border-t border-gray-50 pt-3 space-y-3">
            <!-- Выбор типа документа -->
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ t('crm.docGen.selectType') }}</label>
                <SearchSelect v-model="form.type" :items="typeItems" />
            </div>

            <!-- Доп. параметры в зависимости от типа -->
            <template v-if="form.type === 'cover_letter'">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">{{ t('crm.docGen.travelPurpose') }}</label>
                    <input v-model="form.travel_purpose" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-indigo-300 focus:border-indigo-300 outline-none" />
                </div>
            </template>

            <template v-if="form.type === 'employer_reference'">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">{{ t('crm.docGen.employerName') }}</label>
                    <input v-model="form.employer_name" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-indigo-300 focus:border-indigo-300 outline-none" />
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">{{ t('crm.docGen.position') }}</label>
                    <input v-model="form.position" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-indigo-300 focus:border-indigo-300 outline-none" />
                </div>
            </template>

            <template v-if="form.type === 'sponsor_letter'">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">{{ t('crm.docGen.sponsorName') }}</label>
                    <input v-model="form.sponsor_name" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-indigo-300 focus:border-indigo-300 outline-none" />
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">{{ t('crm.docGen.sponsorRelation') }}</label>
                    <input v-model="form.sponsor_relation" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-indigo-300 focus:border-indigo-300 outline-none" />
                </div>
            </template>

            <template v-if="form.type === 'travel_plan'">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">{{ t('crm.docGen.cities') }}</label>
                    <input v-model="form.cities" type="text" placeholder="Paris, Nice, Lyon" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-indigo-300 focus:border-indigo-300 outline-none" />
                </div>
            </template>

            <!-- Язык -->
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ t('crm.docGen.language') }}</label>
                <SearchSelect v-model="form.language" :items="langItems" />
            </div>

            <!-- Кнопка -->
            <button @click="generate" :disabled="!form.type || loading"
                class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 active:scale-[0.98] transition-all disabled:opacity-50">
                {{ loading ? t('crm.docGen.generating') : t('crm.docGen.generate') }}
            </button>

            <!-- Результат -->
            <div v-if="result" class="bg-gray-50 rounded-xl p-4 mt-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-500">{{ t('crm.docGen.result') }}</span>
                    <button @click="copyResult" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                        {{ copied ? t('crm.docGen.copied') : t('crm.docGen.copy') }}
                    </button>
                </div>
                <div class="text-sm text-[#0A1F44] whitespace-pre-wrap leading-relaxed">{{ result }}</div>
            </div>

            <!-- Ошибка -->
            <div v-if="error" class="bg-red-50 text-red-700 text-xs rounded-xl p-3">{{ error }}</div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { casesApi } from '@/api/cases';
import SearchSelect from '@/components/SearchSelect.vue';

const props = defineProps({ caseId: { type: String, required: true } });
const { t } = useI18n();

const open = ref(false);
const loading = ref(false);
const result = ref('');
const error = ref('');
const copied = ref(false);

const form = ref({
    type: '',
    language: 'ru',
    travel_purpose: '',
    employer_name: '',
    position: '',
    sponsor_name: '',
    sponsor_relation: '',
    cities: '',
});

const typeItems = computed(() => [
    { value: 'cover_letter',       label: t('crm.docGen.coverLetter') },
    { value: 'employer_reference', label: t('crm.docGen.employerRef') },
    { value: 'sponsor_letter',     label: t('crm.docGen.sponsorLetter') },
    { value: 'travel_plan',        label: t('crm.docGen.travelPlan') },
]);

const langItems = computed(() => [
    { value: 'ru', label: t('crm.docGen.langRu') },
    { value: 'uz', label: t('crm.docGen.langUz') },
    { value: 'en', label: t('crm.docGen.langEn') },
]);

async function generate() {
    loading.value = true;
    error.value = '';
    result.value = '';
    try {
        const res = await casesApi.generateDoc(props.caseId, form.value);
        result.value = res.data.data.content;
    } catch (e) {
        error.value = e.response?.data?.message || t('crm.docGen.error');
    } finally {
        loading.value = false;
    }
}

function copyResult() {
    navigator.clipboard.writeText(result.value);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}
</script>
