<template>
    <div class="space-y-5">

        <div class="flex items-center gap-3">
            <select v-model="filterCat" @change="load"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="">Все категории</option>
                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
            <span class="text-sm text-gray-400 ml-auto">{{ docs.length }} шаблонов</span>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">Документ</th>
                        <th class="px-4 py-3 text-left">Категория</th>
                        <th class="px-4 py-3 text-center">Тип</th>
                        <th class="px-4 py-3 text-center">Стран</th>
                        <th class="px-4 py-3 text-center">Статус</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 8" :key="i">
                        <td colspan="5" class="px-5 py-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse w-3/4"></div>
                        </td>
                    </tr>
                    <tr v-else v-for="d in docs" :key="d.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ d.name }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ d.slug }}</div>
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
                            <span class="font-mono text-gray-700">{{ d.country_requirements_count }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="d.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-400'"
                                class="text-xs px-2 py-1 rounded-full">
                                {{ d.is_active ? 'Активен' : 'Откл.' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index';

const docs      = ref([]);
const loading   = ref(true);
const filterCat = ref('');

const categories = [
    { value: 'personal',     label: 'Личные' },
    { value: 'financial',    label: 'Финансовые' },
    { value: 'family',       label: 'Семья' },
    { value: 'property',     label: 'Имущество' },
    { value: 'travel',       label: 'Поездки' },
    { value: 'employment',   label: 'Работа' },
    { value: 'confirmation', label: 'Подтверждение' },
];

const catLabels = Object.fromEntries(categories.map(c => [c.value, c.label]));
const catLabel  = (v) => catLabels[v] ?? v;

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/owner/documents', {
            params: { category: filterCat.value },
        });
        docs.value = data.data;
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>
