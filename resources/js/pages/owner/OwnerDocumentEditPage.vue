<template>
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-32">
        <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <div v-else-if="doc" class="max-w-3xl space-y-6">

        <!-- Header -->
        <div class="flex items-center gap-3">
            <button @click="$router.push({ name: 'owner.documents' })"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg font-bold text-gray-900 truncate">{{ doc.name }}</h1>
                <p class="text-xs text-gray-400 font-mono">{{ doc.slug }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button @click="toggleActive"
                    :class="doc.is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'"
                    class="text-xs px-3 py-1.5 rounded-lg font-medium transition-colors">
                    {{ doc.is_active ? t('owner.documents.active') : t('owner.documents.disabled') }}
                </button>
                <button @click="confirmDelete"
                    class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-400 hover:bg-red-50 font-medium transition-colors">
                    {{ t('common.delete') }}
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 border-b border-gray-200">
            <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                :class="['px-4 py-2.5 text-sm font-medium transition-colors -mb-px border-b-2',
                    activeTab === tab.key ? 'border-[#1BA97F] text-gray-800' : 'border-transparent text-gray-400 hover:text-gray-600']">
                {{ tab.label }}
            </button>
        </div>

        <!-- ========== TAB: BASIC ========== -->
        <div v-if="activeTab === 'basic'" class="space-y-5">
            <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">

                <!-- name -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.name') }}</label>
                    <input v-model="form.name" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
                </div>

                <!-- category + type -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.categoryLabel') }}</label>
                        <SearchSelect v-model="form.category" :items="categories" allow-all :all-label="t('owner.documents.select')" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.typeLabel') }}</label>
                        <SearchSelect v-model="form.type" :items="typeOptions" />
                    </div>
                </div>

                <!-- description -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.description') }}</label>
                    <textarea v-model="form.description" rows="3" maxlength="500"
                        :placeholder="t('owner.documents.descriptionPlaceholder')"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
                    <p class="text-xs text-right mt-0.5" :class="(form.description?.length ?? 0) >= 500 ? 'text-red-500' : 'text-gray-400'">
                        {{ form.description?.length ?? 0 }}/500
                    </p>
                </div>

                <!-- sort_order + flags -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.sortOrder') }}</label>
                        <input v-model.number="form.sort_order" type="number" min="0" max="999"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="flex items-center gap-2 cursor-pointer pb-2.5">
                            <input type="checkbox" v-model="form.is_repeatable" class="accent-[#1BA97F] w-4 h-4"/>
                            <span class="text-sm text-gray-700">{{ t('owner.documents.repeatableCheckbox') }}</span>
                        </label>
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="flex items-center gap-2 cursor-pointer pb-2.5">
                            <input type="checkbox" v-model="form.translation_required" class="accent-[#1BA97F] w-4 h-4"/>
                            <span class="text-sm text-gray-700">{{ t('owner.documents.translationRequired') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== TAB: AI ========== -->
        <div v-if="activeTab === 'ai'" class="space-y-5">

            <!-- AI toggle + max age -->
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.ai_enabled" class="accent-[#1BA97F] w-5 h-5"/>
                            <span class="text-sm font-medium text-gray-800">{{ t('owner.documents.aiEnabled') }}</span>
                        </label>
                        <p class="text-xs text-gray-400 mt-0.5 ml-7">{{ t('owner.documents.aiEnabledHint') }}</p>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.maxAgeDays') }}</label>
                        <input v-model.number="form.max_age_days" type="number" min="0" max="3650"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                    </div>
                </div>
            </div>

            <!-- Extraction schema -->
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">{{ t('owner.documents.extractionSchema') }}</h3>
                        <p class="text-xs text-gray-400">{{ t('owner.documents.extractionSchemaHint') }}</p>
                    </div>
                    <button type="button" @click="form.extraction_fields.push({ name: '', type: 'string' })"
                        class="text-xs text-[#1BA97F] hover:text-[#169B72] font-medium">{{ t('owner.documents.extractionAddField') }}</button>
                </div>
                <div v-if="form.extraction_fields.length" class="space-y-2">
                    <div v-for="(f, i) in form.extraction_fields" :key="i" class="flex items-center gap-2">
                        <input v-model="f.name" :placeholder="t('owner.documents.extractionFieldName')"
                            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] font-mono"/>
                        <select v-model="f.type"
                            class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm outline-none focus:border-[#1BA97F] bg-white w-28">
                            <option value="string">string</option>
                            <option value="date">date</option>
                            <option value="integer">integer</option>
                            <option value="decimal">decimal</option>
                            <option value="boolean">boolean</option>
                        </select>
                        <button type="button" @click="form.extraction_fields.splice(i, 1)"
                            class="text-gray-300 hover:text-red-500 transition-colors shrink-0 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <p v-else class="text-xs text-gray-400 py-3">{{ t('owner.documents.extractionNoFields') }}</p>
            </div>

            <!-- Validation rules -->
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">{{ t('owner.documents.validationRules') }}</h3>
                        <p class="text-xs text-gray-400">{{ t('owner.documents.validationRulesHint') }}</p>
                    </div>
                    <button type="button" @click="form.validation_rules.push({ field: '', rule: 'not_empty' })"
                        class="text-xs text-[#1BA97F] hover:text-[#169B72] font-medium">{{ t('owner.documents.validationAddRule') }}</button>
                </div>
                <div v-if="form.validation_rules.length" class="space-y-2">
                    <div v-for="(r, i) in form.validation_rules" :key="i" class="flex items-center gap-2">
                        <input v-model="r.field" :placeholder="t('owner.documents.validationField')"
                            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] font-mono"/>
                        <select v-model="r.rule"
                            class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm outline-none focus:border-[#1BA97F] bg-white w-36">
                            <option value="not_empty">not_empty</option>
                            <option value="must_be_future">must_be_future</option>
                            <option value="must_be_past">must_be_past</option>
                            <option value="min_amount">min_amount</option>
                        </select>
                        <button type="button" @click="form.validation_rules.splice(i, 1)"
                            class="text-gray-300 hover:text-red-500 transition-colors shrink-0 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== TAB: MANAGER ========== -->
        <div v-if="activeTab === 'manager'" class="space-y-5">

            <!-- Manager instructions -->
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">{{ t('owner.documents.managerInstructions') }}</h3>
                <p class="text-xs text-gray-400 mb-3">{{ t('owner.documents.managerInstructionsHint') }}</p>
                <textarea v-model="form.manager_instructions" rows="6"
                    :placeholder="t('owner.documents.managerInstructionsPlaceholder')"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
            </div>

            <!-- Risk indicators -->
            <div class="bg-white rounded-xl border border-amber-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-amber-700">{{ t('owner.documents.riskIndicators') }}</h3>
                        <p class="text-xs text-gray-400">{{ t('owner.documents.riskIndicatorsHint') }}</p>
                    </div>
                    <button type="button" @click="form.risk_indicators.push('')"
                        class="text-xs text-[#1BA97F] hover:text-[#169B72] font-medium">{{ t('owner.documents.addItem') }}</button>
                </div>
                <div v-if="form.risk_indicators.length" class="space-y-2">
                    <div v-for="(_, i) in form.risk_indicators" :key="i" class="flex items-center gap-2">
                        <input v-model="form.risk_indicators[i]" placeholder="..."
                            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-amber-400"/>
                        <button type="button" @click="form.risk_indicators.splice(i, 1)"
                            class="text-gray-300 hover:text-red-500 transition-colors shrink-0 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stop factors -->
            <div class="bg-white rounded-xl border border-red-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-red-600">{{ t('owner.documents.stopFactors') }}</h3>
                        <p class="text-xs text-gray-400">{{ t('owner.documents.stopFactorsHint') }}</p>
                    </div>
                    <button type="button" @click="form.stop_factors.push('')"
                        class="text-xs text-[#1BA97F] hover:text-[#169B72] font-medium">{{ t('owner.documents.addItem') }}</button>
                </div>
                <div v-if="form.stop_factors.length" class="space-y-2">
                    <div v-for="(_, i) in form.stop_factors" :key="i" class="flex items-center gap-2">
                        <input v-model="form.stop_factors[i]" placeholder="passport_expired"
                            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-red-400 font-mono"/>
                        <button type="button" @click="form.stop_factors.splice(i, 1)"
                            class="text-gray-300 hover:text-red-500 transition-colors shrink-0 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Success factors -->
            <div class="bg-white rounded-xl border border-green-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-green-600">{{ t('owner.documents.successFactors') }}</h3>
                        <p class="text-xs text-gray-400">{{ t('owner.documents.successFactorsHint') }}</p>
                    </div>
                    <button type="button" @click="form.success_factors.push('')"
                        class="text-xs text-[#1BA97F] hover:text-[#169B72] font-medium">{{ t('owner.documents.addItem') }}</button>
                </div>
                <div v-if="form.success_factors.length" class="space-y-2">
                    <div v-for="(_, i) in form.success_factors" :key="i" class="flex items-center gap-2">
                        <input v-model="form.success_factors[i]" placeholder="high_balance"
                            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-green-400 font-mono"/>
                        <button type="button" @click="form.success_factors.splice(i, 1)"
                            class="text-gray-300 hover:text-red-500 transition-colors shrink-0 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save bar (sticky bottom) -->
        <div class="sticky bottom-0 bg-white border-t border-gray-100 -mx-6 px-6 py-4 flex items-center justify-between">
            <p v-if="saveMsg" :class="['text-sm font-medium', saveMsgType === 'error' ? 'text-red-500' : 'text-green-600']">{{ saveMsg }}</p>
            <span v-else></span>
            <button @click="save" :disabled="saving"
                class="bg-[#1BA97F] text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-[#169B72] transition-colors disabled:opacity-60">
                {{ saving ? t('owner.documents.saving') : t('common.save') }}
            </button>
        </div>

    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();
const route  = useRoute();
const router = useRouter();
const id     = route.params.id;

const doc     = ref(null);
const loading = ref(true);
const saving  = ref(false);
const saveMsg = ref('');
const saveMsgType = ref('success');
const activeTab = ref('basic');

const tabs = computed(() => [
    { key: 'basic',   label: t('owner.documents.tabBasic') },
    { key: 'ai',      label: t('owner.documents.tabAi') },
    { key: 'manager', label: t('owner.documents.tabManager') },
]);

const form = reactive({
    name:                 '',
    category:             '',
    type:                 'upload',
    description:          '',
    is_repeatable:        false,
    sort_order:           0,
    ai_enabled:           true,
    manager_instructions: '',
    translation_required: false,
    max_age_days:         0,
    extraction_fields:    [],
    validation_rules:     [],
    stop_factors:         [],
    success_factors:      [],
    risk_indicators:      [],
});

const categories = computed(() => [
    { value: 'personal',     label: t('owner.documents.catPersonal') },
    { value: 'financial',    label: t('owner.documents.catFinancial') },
    { value: 'family',       label: t('owner.documents.catFamily') },
    { value: 'property',     label: t('owner.documents.catProperty') },
    { value: 'travel',       label: t('owner.documents.catTravel') },
    { value: 'employment',   label: t('owner.documents.catEmployment') },
    { value: 'confirmation', label: t('owner.documents.catConfirmation') },
    { value: 'other',        label: t('owner.documents.catOther') },
]);

const typeOptions = computed(() => [
    { value: 'upload', label: t('owner.documents.fileUpload') },
    { value: 'checkbox', label: t('owner.documents.checkbox') },
]);

// Helpers
function schemaToFields(schema) {
    if (!schema || typeof schema !== 'object') return [];
    return Object.entries(schema).map(([name, type]) => ({ name, type: type || 'string' }));
}
function fieldsToSchema(fields) {
    if (!fields.length) return null;
    const obj = {};
    for (const f of fields) { if (f.name.trim()) obj[f.name.trim()] = f.type || 'string'; }
    return Object.keys(obj).length ? obj : null;
}
function rulesObjToArr(rules) {
    if (!rules || typeof rules !== 'object') return [];
    return Object.entries(rules).map(([field, rule]) => ({ field, rule }));
}
function rulesArrToObj(arr) {
    if (!arr.length) return null;
    const obj = {};
    for (const r of arr) { if (r.field.trim()) obj[r.field.trim()] = r.rule || 'not_empty'; }
    return Object.keys(obj).length ? obj : null;
}

function populateForm(d) {
    form.name                 = d.name ?? '';
    form.category             = d.category ?? '';
    form.type                 = d.type ?? 'upload';
    form.description          = d.description ?? '';
    form.is_repeatable        = !!d.is_repeatable;
    form.sort_order           = d.sort_order ?? 0;
    form.ai_enabled           = d.ai_enabled ?? true;
    form.manager_instructions = d.manager_instructions ?? '';
    form.translation_required = !!d.translation_required;
    form.max_age_days         = d.max_age_days ?? 0;
    form.extraction_fields    = schemaToFields(d.ai_extraction_schema);
    form.validation_rules     = rulesObjToArr(d.ai_validation_rules);
    form.stop_factors         = Array.isArray(d.ai_stop_factors) ? [...d.ai_stop_factors] : [];
    form.success_factors      = Array.isArray(d.ai_success_factors) ? [...d.ai_success_factors] : [];
    form.risk_indicators      = Array.isArray(d.ai_risk_indicators) ? [...d.ai_risk_indicators] : [];
}

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get(`/admin/document-templates/${id}`);
        doc.value = data.data;
        populateForm(doc.value);
    } finally {
        loading.value = false;
    }
}

async function save() {
    saving.value  = true;
    saveMsg.value = '';
    try {
        const payload = {
            name:                 form.name,
            category:             form.category,
            type:                 form.type,
            description:          form.description || null,
            is_repeatable:        form.is_repeatable,
            sort_order:           form.sort_order,
            ai_enabled:           form.ai_enabled,
            manager_instructions: form.manager_instructions || null,
            translation_required: form.translation_required,
            max_age_days:         form.max_age_days || null,
            ai_extraction_schema: fieldsToSchema(form.extraction_fields),
            ai_validation_rules:  rulesArrToObj(form.validation_rules),
            ai_stop_factors:      form.stop_factors.filter(s => s.trim()),
            ai_success_factors:   form.success_factors.filter(s => s.trim()),
            ai_risk_indicators:   form.risk_indicators.filter(s => s.trim()),
        };
        const { data } = await api.patch(`/admin/document-templates/${id}`, payload);
        doc.value = data.data;
        saveMsgType.value = 'success';
        saveMsg.value = t('owner.documents.saved');
        setTimeout(() => { saveMsg.value = ''; }, 3000);
    } catch (e) {
        saveMsgType.value = 'error';
        saveMsg.value = e.response?.data?.message ?? t('owner.documents.saveError');
    } finally {
        saving.value = false;
    }
}

async function toggleActive() {
    try {
        await api.patch(`/admin/document-templates/${id}`, { is_active: !doc.value.is_active });
        doc.value.is_active = !doc.value.is_active;
    } catch { /* ignore */ }
}

async function confirmDelete() {
    if (!confirm(t('owner.documents.deleteTemplate'))) return;
    try {
        await api.delete(`/admin/document-templates/${id}`);
        router.push({ name: 'owner.documents' });
    } catch (e) {
        saveMsg.value = e.response?.data?.message ?? t('owner.documents.deleteError');
        saveMsgType.value = 'error';
    }
}

onMounted(load);
</script>
