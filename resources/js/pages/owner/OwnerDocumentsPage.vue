<template>
    <div class="space-y-5">

        <!-- Toolbar -->
        <div class="flex items-center gap-3">
            <select v-model="filterCat" @change="load"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="">Все категории</option>
                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
            <span class="text-sm text-gray-400">{{ docs.length }} шаблонов</span>
            <button @click="openCreate"
                class="ml-auto flex items-center gap-2 bg-[#1BA97F] text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-[#169B72] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Добавить документ
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">Документ</th>
                        <th class="px-4 py-3 text-left">Категория</th>
                        <th class="px-4 py-3 text-center">Тип</th>
                        <th class="px-4 py-3 text-center">Повт.</th>
                        <th class="px-4 py-3 text-center">Стран</th>
                        <th class="px-4 py-3 text-center">Статус</th>
                        <th class="px-4 py-3 text-right">Действия</th>
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
                            Шаблонов не найдено
                        </td>
                    </tr>
                    <tr v-else v-for="d in docs" :key="d.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ d.name }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ d.slug }}</div>
                            <div v-if="d.description" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ d.description }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                {{ catLabel(d.category) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-1 rounded-full"
                                :class="d.type === 'upload' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700'">
                                {{ d.type === 'upload' ? 'Файл' : 'Чекбокс' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs" :class="d.is_repeatable ? 'text-green-600' : 'text-gray-300'">
                                {{ d.is_repeatable ? 'Да' : 'Нет' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-gray-700">{{ d.country_requirements_count ?? 0 }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="toggleActive(d)"
                                :class="d.is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'"
                                class="text-xs px-2 py-1 rounded-full transition-colors cursor-pointer">
                                {{ d.is_active ? 'Активен' : 'Откл.' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openEdit(d)"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                    Изменить
                                </button>
                                <button @click="confirmDelete(d)"
                                    class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors">
                                    Удалить
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
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{ editTarget ? 'Редактировать шаблон' : 'Новый шаблон документа' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveDoc" class="px-6 py-5 space-y-4">
                    <!-- slug -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Slug <span class="text-red-500">*</span></label>
                        <input v-model="form.slug" :readonly="!!editTarget" required
                            placeholder="passport_copy"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] font-mono"
                            :class="editTarget ? 'bg-gray-50 text-gray-400' : ''"/>
                        <p v-if="!editTarget" class="text-xs text-gray-400 mt-1">Латиница, цифры, подчёркивание. Не изменяется после создания.</p>
                    </div>

                    <!-- name -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Название <span class="text-red-500">*</span></label>
                        <input v-model="form.name" required placeholder="Копия паспорта"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
                    </div>

                    <!-- category + type -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Категория <span class="text-red-500">*</span></label>
                            <select v-model="form.category" required
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]">
                                <option value="">Выберите</option>
                                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                                <option value="other">Прочее</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Тип <span class="text-red-500">*</span></label>
                            <select v-model="form.type" required
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]">
                                <option value="upload">Файл (загрузка)</option>
                                <option value="checkbox">Чекбокс</option>
                            </select>
                        </div>
                    </div>

                    <!-- description -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Описание</label>
                        <textarea v-model="form.description" rows="3" maxlength="500"
                            placeholder="Что именно нужно предоставить..."
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
                        <p class="text-xs text-right mt-0.5" :class="form.description.length >= 500 ? 'text-red-500' : 'text-gray-400'">
                            {{ form.description.length }}/500
                        </p>
                    </div>

                    <!-- sort_order + is_repeatable + is_active -->
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Порядок</label>
                            <input v-model.number="form.sort_order" type="number" min="0" max="999"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
                        </div>
                        <div class="flex flex-col justify-end">
                            <label class="flex items-center gap-2 cursor-pointer pb-2.5">
                                <input type="checkbox" v-model="form.is_repeatable" class="accent-[#1BA97F] w-4 h-4"/>
                                <span class="text-sm text-gray-700">Повторяемый</span>
                            </label>
                        </div>
                        <div class="flex flex-col justify-end">
                            <label class="flex items-center gap-2 cursor-pointer pb-2.5">
                                <input type="checkbox" v-model="form.is_active" class="accent-[#1BA97F] w-4 h-4"/>
                                <span class="text-sm text-gray-700">Активен</span>
                            </label>
                        </div>
                    </div>

                    <!-- error -->
                    <p v-if="formError" class="text-sm text-red-500 bg-red-50 px-3 py-2 rounded-xl">{{ formError }}</p>

                    <!-- actions -->
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="closeModal"
                            class="flex-1 border border-gray-200 text-gray-600 rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-gray-50 transition-colors">
                            Отмена
                        </button>
                        <button type="submit" :disabled="saving"
                            class="flex-1 bg-[#1BA97F] text-white rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-[#169B72] transition-colors disabled:opacity-60">
                            {{ saving ? 'Сохранение...' : (editTarget ? 'Сохранить' : 'Создать') }}
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
                <h3 class="text-base font-semibold text-gray-800 mb-2">Удалить шаблон?</h3>
                <p class="text-sm text-gray-500 mb-1">
                    <span class="font-medium text-gray-700">{{ deleteTarget.name }}</span>
                </p>
                <p class="text-xs text-gray-400 mb-5">
                    Шаблон нельзя удалить, если он привязан к требованиям стран.
                </p>
                <p v-if="deleteError" class="text-sm text-red-500 bg-red-50 px-3 py-2 rounded-xl mb-4">{{ deleteError }}</p>
                <div class="flex gap-3">
                    <button @click="deleteTarget = null; deleteError = ''"
                        class="flex-1 border border-gray-200 text-gray-600 rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-gray-50 transition-colors">
                        Отмена
                    </button>
                    <button @click="doDelete" :disabled="deleting"
                        class="flex-1 bg-red-600 text-white rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-60">
                        {{ deleting ? 'Удаление...' : 'Удалить' }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/api/index';

const docs      = ref([]);
const loading   = ref(true);
const filterCat = ref('');

// modal
const showModal  = ref(false);
const editTarget = ref(null);
const saving     = ref(false);
const formError  = ref('');

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
});

const categories = [
    { value: 'personal',     label: 'Личные' },
    { value: 'financial',    label: 'Финансовые' },
    { value: 'family',       label: 'Семья' },
    { value: 'property',     label: 'Имущество' },
    { value: 'travel',       label: 'Поездки' },
    { value: 'employment',   label: 'Работа' },
    { value: 'confirmation', label: 'Подтверждение' },
    { value: 'other',        label: 'Прочее' },
];

const catLabels = Object.fromEntries(categories.map(c => [c.value, c.label]));
const catLabel  = (v) => catLabels[v] ?? v;

async function load() {
    loading.value = true;
    try {
        const params = {};
        if (filterCat.value) params.category = filterCat.value;
        const { data } = await api.get('/admin/document-templates', { params });
        docs.value = data.data;
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    form.slug          = '';
    form.name          = '';
    form.category      = '';
    form.type          = 'upload';
    form.description   = '';
    form.is_repeatable = false;
    form.is_active     = true;
    form.sort_order    = 0;
    formError.value    = '';
}

function openCreate() {
    editTarget.value = null;
    resetForm();
    showModal.value = true;
}

function openEdit(doc) {
    editTarget.value       = doc;
    form.slug              = doc.slug;
    form.name              = doc.name;
    form.category          = doc.category;
    form.type              = doc.type;
    form.description       = doc.description ?? '';
    form.is_repeatable     = !!doc.is_repeatable;
    form.is_active         = !!doc.is_active;
    form.sort_order        = doc.sort_order ?? 0;
    formError.value        = '';
    showModal.value        = true;
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
            name:          form.name,
            category:      form.category,
            type:          form.type,
            description:   form.description || null,
            is_repeatable: form.is_repeatable,
            is_active:     form.is_active,
            sort_order:    form.sort_order,
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
        formError.value = e.response?.data?.message ?? 'Ошибка при сохранении';
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
        deleteError.value = e.response?.data?.message ?? 'Не удалось удалить шаблон';
    } finally {
        deleting.value = false;
    }
}

onMounted(load);
</script>
