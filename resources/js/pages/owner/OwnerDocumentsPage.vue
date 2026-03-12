<template>
    <div class="space-y-5">

        <!-- Toolbar -->
        <div class="flex items-center gap-3">
            <SearchSelect v-model="filterCat" @change="load" compact
                :items="categories" allow-all :all-label="t('owner.documents.allCategories')" />
            <span class="text-sm text-gray-400">{{ t('owner.documents.templatesCount', { count: docs.length }) }}</span>
            <button @click="openCreate"
                class="ml-auto flex items-center gap-2 bg-[#1BA97F] text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-[#169B72] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ t('owner.documents.addDocument') }}
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">{{ t('owner.documents.document') }}</th>
                        <th class="px-4 py-3 text-left">{{ t('owner.documents.category') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.type') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.aiColumn') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.countriesCount') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.status') }}</th>
                        <th class="px-4 py-3 text-right">{{ t('owner.documents.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 8" :key="i">
                        <td colspan="7" class="px-5 py-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse w-3/4"></div>
                        </td>
                    </tr>
                    <tr v-else-if="!docs.length">
                        <td colspan="7" class="px-5 py-10 text-center text-gray-400 text-sm">
                            {{ t('owner.documents.noTemplates') }}
                        </td>
                    </tr>
                    <tr v-else v-for="d in docs" :key="d.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ d.name }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ d.slug }}</div>
                            <div v-if="d.manager_instructions" class="text-xs text-amber-600 mt-0.5 line-clamp-1">{{ d.manager_instructions }}</div>
                            <div v-else-if="d.description" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ d.description }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                {{ catLabel(d.category) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-1 rounded-full"
                                :class="d.type === 'upload' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700'">
                                {{ d.type === 'upload' ? t('owner.documents.file') : t('owner.documents.checkbox') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="toggleAi(d)"
                                :class="d.ai_enabled ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'"
                                class="text-xs px-2 py-1 rounded-full transition-colors cursor-pointer">
                                {{ d.ai_enabled ? 'ON' : 'OFF' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-gray-700">{{ d.country_requirements_count ?? 0 }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="toggleActive(d)"
                                :class="d.is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'"
                                class="text-xs px-2 py-1 rounded-full transition-colors cursor-pointer">
                                {{ d.is_active ? t('owner.documents.active') : t('owner.documents.disabled') }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openEdit(d)"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                    {{ t('common.edit') }}
                                </button>
                                <button @click="confirmDelete(d)"
                                    class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors">
                                    {{ t('common.delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create / Edit modal -->
        <div v-if="showModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            @click.self="closeModal">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{ editTarget ? t('owner.documents.editTemplate') : t('owner.documents.newTemplate') }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="px-6 pt-4 flex gap-1 border-b border-gray-100">
                    <button @click="activeTab = 'basic'" :class="['px-4 py-2 text-sm font-medium rounded-t-lg transition-colors -mb-px', activeTab === 'basic' ? 'bg-white border border-b-white border-gray-100 text-gray-800' : 'text-gray-400 hover:text-gray-600']">
                        {{ t('owner.documents.tabBasic') }}
                    </button>
                    <button @click="activeTab = 'ai'" :class="['px-4 py-2 text-sm font-medium rounded-t-lg transition-colors -mb-px', activeTab === 'ai' ? 'bg-white border border-b-white border-gray-100 text-gray-800' : 'text-gray-400 hover:text-gray-600']">
                        {{ t('owner.documents.tabAi') }}
                    </button>
                </div>

                <form @submit.prevent="saveDoc" class="px-6 py-5 space-y-4">

                    <!-- ========== TAB: BASIC ========== -->
                    <template v-if="activeTab === 'basic'">
                        <!-- slug -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.slug') }} <span class="text-red-500">*</span></label>
                            <input v-model="form.slug" :readonly="!!editTarget" required
                                placeholder="passport_copy"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] font-mono"
                                :class="editTarget ? 'bg-gray-50 text-gray-400' : ''"/>
                            <p v-if="!editTarget" class="text-xs text-gray-400 mt-1">{{ t('owner.documents.slugHint') }}</p>
                        </div>

                        <!-- name -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.name') }} <span class="text-red-500">*</span></label>
                            <input v-model="form.name" required placeholder="Копия паспорта"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
                        </div>

                        <!-- category + type -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.categoryLabel') }} <span class="text-red-500">*</span></label>
                                <SearchSelect v-model="form.category" required
                                    :items="formCategoryOptions" allow-all :all-label="t('owner.documents.select')" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.typeLabel') }} <span class="text-red-500">*</span></label>
                                <SearchSelect v-model="form.type" required
                                    :items="typeOptions" />
                            </div>
                        </div>

                        <!-- description -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.description') }}</label>
                            <textarea v-model="form.description" rows="3" maxlength="500"
                                :placeholder="t('owner.documents.descriptionPlaceholder')"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
                            <p class="text-xs text-right mt-0.5" :class="form.description.length >= 500 ? 'text-red-500' : 'text-gray-400'">
                                {{ form.description.length }}/500
                            </p>
                        </div>

                        <!-- sort_order + is_repeatable + is_active -->
                        <div class="grid grid-cols-3 gap-3">
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
                                    <input type="checkbox" v-model="form.is_active" class="accent-[#1BA97F] w-4 h-4"/>
                                    <span class="text-sm text-gray-700">{{ t('owner.documents.activeCheckbox') }}</span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <!-- ========== TAB: AI ========== -->
                    <template v-if="activeTab === 'ai'">

                        <!-- AI toggle + translation + max age -->
                        <div class="grid grid-cols-3 gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="form.ai_enabled" class="accent-[#1BA97F] w-4 h-4"/>
                                <span class="text-sm text-gray-700">{{ t('owner.documents.aiEnabled') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="form.translation_required" class="accent-[#1BA97F] w-4 h-4"/>
                                <span class="text-sm text-gray-700">{{ t('owner.documents.translationRequired') }}</span>
                            </label>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.maxAgeDays') }}</label>
                                <input v-model.number="form.max_age_days" type="number" min="0" max="3650"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ t('owner.documents.maxAgeDaysHint') }}</p>
                            </div>
                        </div>

                        <!-- Manager instructions -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.managerInstructions') }}</label>
                            <textarea v-model="form.manager_instructions" rows="4"
                                :placeholder="t('owner.documents.managerInstructionsPlaceholder')"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ t('owner.documents.managerInstructionsHint') }}</p>
                        </div>

                        <!-- Extraction schema -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <label class="text-xs font-medium text-gray-600">{{ t('owner.documents.extractionSchema') }}</label>
                                    <p class="text-[10px] text-gray-400">{{ t('owner.documents.extractionSchemaHint') }}</p>
                                </div>
                                <button type="button" @click="addExtractionField"
                                    class="text-xs text-[#1BA97F] hover:text-[#169B72] font-medium">{{ t('owner.documents.extractionAddField') }}</button>
                            </div>
                            <div v-if="form.extraction_fields.length" class="space-y-1.5">
                                <div v-for="(f, i) in form.extraction_fields" :key="i" class="flex items-center gap-2">
                                    <input v-model="f.name" :placeholder="t('owner.documents.extractionFieldName')"
                                        class="flex-1 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs outline-none focus:border-[#1BA97F] font-mono"/>
                                    <select v-model="f.type"
                                        class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs outline-none focus:border-[#1BA97F] bg-white">
                                        <option value="string">string</option>
                                        <option value="date">date</option>
                                        <option value="integer">integer</option>
                                        <option value="decimal">decimal</option>
                                        <option value="boolean">boolean</option>
                                    </select>
                                    <button type="button" @click="form.extraction_fields.splice(i, 1)"
                                        class="text-gray-300 hover:text-red-500 transition-colors shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                            <p v-else class="text-xs text-gray-400 py-2">{{ t('owner.documents.extractionNoFields') }}</p>
                        </div>

                        <!-- Validation rules -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-medium text-gray-600">{{ t('owner.documents.validationRules') }}</label>
                                <button type="button" @click="addValidationRule"
                                    class="text-xs text-[#1BA97F] hover:text-[#169B72] font-medium">{{ t('owner.documents.validationAddRule') }}</button>
                            </div>
                            <div v-if="form.validation_rules.length" class="space-y-1.5">
                                <div v-for="(r, i) in form.validation_rules" :key="i" class="flex items-center gap-2">
                                    <input v-model="r.field" :placeholder="t('owner.documents.validationField')"
                                        class="flex-1 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs outline-none focus:border-[#1BA97F] font-mono"/>
                                    <select v-model="r.rule"
                                        class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs outline-none focus:border-[#1BA97F] bg-white">
                                        <option value="not_empty">not_empty</option>
                                        <option value="must_be_future">must_be_future</option>
                                        <option value="must_be_past">must_be_past</option>
                                        <option value="min_amount">min_amount</option>
                                    </select>
                                    <button type="button" @click="form.validation_rules.splice(i, 1)"
                                        class="text-gray-300 hover:text-red-500 transition-colors shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Stop factors -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <label class="text-xs font-medium text-red-600">{{ t('owner.documents.stopFactors') }}</label>
                                    <p class="text-[10px] text-gray-400">{{ t('owner.documents.stopFactorsHint') }}</p>
                                </div>
                                <button type="button" @click="form.stop_factors.push('')"
                                    class="text-xs text-[#1BA97F] hover:text-[#169B72] font-medium">{{ t('owner.documents.addItem') }}</button>
                            </div>
                            <div v-if="form.stop_factors.length" class="space-y-1">
                                <div v-for="(_, i) in form.stop_factors" :key="i" class="flex items-center gap-2">
                                    <input v-model="form.stop_factors[i]" placeholder="passport_expired"
                                        class="flex-1 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs outline-none focus:border-red-400 font-mono"/>
                                    <button type="button" @click="form.stop_factors.splice(i, 1)"
                                        class="text-gray-300 hover:text-red-500 transition-colors shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Success factors -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <label class="text-xs font-medium text-green-600">{{ t('owner.documents.successFactors') }}</label>
                                    <p class="text-[10px] text-gray-400">{{ t('owner.documents.successFactorsHint') }}</p>
                                </div>
                                <button type="button" @click="form.success_factors.push('')"
                                    class="text-xs text-[#1BA97F] hover:text-[#169B72] font-medium">{{ t('owner.documents.addItem') }}</button>
                            </div>
                            <div v-if="form.success_factors.length" class="space-y-1">
                                <div v-for="(_, i) in form.success_factors" :key="i" class="flex items-center gap-2">
                                    <input v-model="form.success_factors[i]" placeholder="high_balance"
                                        class="flex-1 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs outline-none focus:border-green-400 font-mono"/>
                                    <button type="button" @click="form.success_factors.splice(i, 1)"
                                        class="text-gray-300 hover:text-red-500 transition-colors shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Risk indicators -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <label class="text-xs font-medium text-amber-600">{{ t('owner.documents.riskIndicators') }}</label>
                                    <p class="text-[10px] text-gray-400">{{ t('owner.documents.riskIndicatorsHint') }}</p>
                                </div>
                                <button type="button" @click="form.risk_indicators.push('')"
                                    class="text-xs text-[#1BA97F] hover:text-[#169B72] font-medium">{{ t('owner.documents.addItem') }}</button>
                            </div>
                            <div v-if="form.risk_indicators.length" class="space-y-1">
                                <div v-for="(_, i) in form.risk_indicators" :key="i" class="flex items-center gap-2">
                                    <input v-model="form.risk_indicators[i]" placeholder="Описание риска..."
                                        class="flex-1 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs outline-none focus:border-amber-400"/>
                                    <button type="button" @click="form.risk_indicators.splice(i, 1)"
                                        class="text-gray-300 hover:text-red-500 transition-colors shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </template>

                    <!-- error -->
                    <p v-if="formError" class="text-sm text-red-500 bg-red-50 px-3 py-2 rounded-xl">{{ formError }}</p>

                    <!-- actions -->
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="closeModal"
                            class="flex-1 border border-gray-200 text-gray-600 rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-gray-50 transition-colors">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" :disabled="saving"
                            class="flex-1 bg-[#1BA97F] text-white rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-[#169B72] transition-colors disabled:opacity-60">
                            {{ saving ? t('owner.documents.saving') : (editTarget ? t('common.save') : t('owner.documents.create')) }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete confirm modal -->
        <div v-if="deleteTarget"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            @click.self="deleteTarget = null">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-2">{{ t('owner.documents.deleteTemplate') }}</h3>
                <p class="text-sm text-gray-500 mb-1">
                    <span class="font-medium text-gray-700">{{ deleteTarget.name }}</span>
                </p>
                <p class="text-xs text-gray-400 mb-5">
                    {{ t('owner.documents.deleteWarning') }}
                </p>
                <p v-if="deleteError" class="text-sm text-red-500 bg-red-50 px-3 py-2 rounded-xl mb-4">{{ deleteError }}</p>
                <div class="flex gap-3">
                    <button @click="deleteTarget = null; deleteError = ''"
                        class="flex-1 border border-gray-200 text-gray-600 rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-gray-50 transition-colors">
                        {{ t('common.cancel') }}
                    </button>
                    <button @click="doDelete" :disabled="deleting"
                        class="flex-1 bg-red-600 text-white rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-60">
                        {{ deleting ? t('owner.documents.deleting') : t('common.delete') }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();

const docs      = ref([]);
const loading   = ref(true);
const filterCat = ref('');

// modal
const showModal  = ref(false);
const editTarget = ref(null);
const saving     = ref(false);
const formError  = ref('');
const activeTab  = ref('basic');

// delete
const deleteTarget = ref(null);
const deleting     = ref(false);
const deleteError  = ref('');

const form = reactive({
    slug:          '',
    name:          '',
    category:      '',
    type:          'upload',
    description:   '',
    is_repeatable: false,
    is_active:     true,
    sort_order:    0,
    // AI fields
    ai_enabled:           true,
    manager_instructions: '',
    translation_required: false,
    max_age_days:         0,
    extraction_fields:    [],   // [{name, type}]
    validation_rules:     [],   // [{field, rule}]
    stop_factors:         [],   // [string]
    success_factors:      [],   // [string]
    risk_indicators:      [],   // [string]
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

const formCategoryOptions = computed(() => [
    ...categories.value,
    { value: 'other', label: t('owner.documents.other') },
]);

const typeOptions = computed(() => [
    { value: 'upload', label: t('owner.documents.fileUpload') },
    { value: 'checkbox', label: t('owner.documents.checkbox') },
]);

const catLabelsMap = computed(() => Object.fromEntries(categories.value.map(c => [c.value, c.label])));
const catLabel  = (v) => catLabelsMap.value[v] ?? v;

// Helpers: convert extraction schema object <-> array
function schemaToFields(schema) {
    if (!schema || typeof schema !== 'object') return [];
    return Object.entries(schema).map(([name, type]) => ({ name, type: type || 'string' }));
}
function fieldsToSchema(fields) {
    if (!fields.length) return null;
    const obj = {};
    for (const f of fields) {
        if (f.name.trim()) obj[f.name.trim()] = f.type || 'string';
    }
    return Object.keys(obj).length ? obj : null;
}
// Helpers: convert validation rules object <-> array
function rulesObjToArr(rules) {
    if (!rules || typeof rules !== 'object') return [];
    return Object.entries(rules).map(([field, rule]) => ({ field, rule }));
}
function rulesArrToObj(arr) {
    if (!arr.length) return null;
    const obj = {};
    for (const r of arr) {
        if (r.field.trim()) obj[r.field.trim()] = r.rule || 'not_empty';
    }
    return Object.keys(obj).length ? obj : null;
}

function addExtractionField() {
    form.extraction_fields.push({ name: '', type: 'string' });
}
function addValidationRule() {
    form.validation_rules.push({ field: '', rule: 'not_empty' });
}

async function load() {
    loading.value = true;
    try {
        const params = {};
        if (filterCat.value) params.category = filterCat.value;
        params.active_only = false;
        const { data } = await api.get('/admin/document-templates', { params });
        docs.value = data.data;
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    form.slug                 = '';
    form.name                 = '';
    form.category             = '';
    form.type                 = 'upload';
    form.description          = '';
    form.is_repeatable        = false;
    form.is_active            = true;
    form.sort_order           = 0;
    form.ai_enabled           = true;
    form.manager_instructions = '';
    form.translation_required = false;
    form.max_age_days         = 0;
    form.extraction_fields    = [];
    form.validation_rules     = [];
    form.stop_factors         = [];
    form.success_factors      = [];
    form.risk_indicators      = [];
    formError.value           = '';
    activeTab.value           = 'basic';
}

function openCreate() {
    editTarget.value = null;
    resetForm();
    showModal.value = true;
}

function openEdit(doc) {
    editTarget.value            = doc;
    form.slug                   = doc.slug;
    form.name                   = doc.name;
    form.category               = doc.category;
    form.type                   = doc.type;
    form.description            = doc.description ?? '';
    form.is_repeatable          = !!doc.is_repeatable;
    form.is_active              = !!doc.is_active;
    form.sort_order             = doc.sort_order ?? 0;
    form.ai_enabled             = doc.ai_enabled ?? true;
    form.manager_instructions   = doc.manager_instructions ?? '';
    form.translation_required   = !!doc.translation_required;
    form.max_age_days           = doc.max_age_days ?? 0;
    form.extraction_fields      = schemaToFields(doc.ai_extraction_schema);
    form.validation_rules       = rulesObjToArr(doc.ai_validation_rules);
    form.stop_factors           = Array.isArray(doc.ai_stop_factors) ? [...doc.ai_stop_factors] : [];
    form.success_factors        = Array.isArray(doc.ai_success_factors) ? [...doc.ai_success_factors] : [];
    form.risk_indicators        = Array.isArray(doc.ai_risk_indicators) ? [...doc.ai_risk_indicators] : [];
    formError.value             = '';
    activeTab.value             = 'basic';
    showModal.value             = true;
}

function closeModal() {
    showModal.value  = false;
    editTarget.value = null;
}

async function saveDoc() {
    saving.value    = true;
    formError.value = '';
    try {
        const payload = {
            name:                 form.name,
            category:             form.category,
            type:                 form.type,
            description:          form.description || null,
            is_repeatable:        form.is_repeatable,
            is_active:            form.is_active,
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
        if (!editTarget.value) {
            payload.slug = form.slug;
            await api.post('/admin/document-templates', payload);
        } else {
            await api.patch(`/admin/document-templates/${editTarget.value.id}`, payload);
        }
        closeModal();
        await load();
    } catch (e) {
        formError.value = e.response?.data?.message ?? t('owner.documents.saveError');
    } finally {
        saving.value = false;
    }
}

async function toggleActive(doc) {
    try {
        await api.patch(`/admin/document-templates/${doc.id}`, { is_active: !doc.is_active });
        doc.is_active = !doc.is_active;
    } catch {
        // ignore
    }
}

async function toggleAi(doc) {
    try {
        const { data } = await api.patch(`/admin/document-templates/${doc.id}/toggle-ai`);
        doc.ai_enabled = data.data.ai_enabled;
    } catch {
        // ignore
    }
}

function confirmDelete(doc) {
    deleteTarget.value = doc;
    deleteError.value  = '';
}

async function doDelete() {
    deleting.value    = true;
    deleteError.value = '';
    try {
        await api.delete(`/admin/document-templates/${deleteTarget.value.id}`);
        deleteTarget.value = null;
        await load();
    } catch (e) {
        deleteError.value = e.response?.data?.message ?? t('owner.documents.deleteError');
    } finally {
        deleting.value = false;
    }
}

onMounted(load);
</script>
