<template>
    <div class="space-y-6">

        <!-- Метрики — строка 1: клиенты -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <MetricCard title="Клиентов всего"  :value="fmt(stats.public_users?.total)"     icon="👤" color="blue" />
            <MetricCard title="Новых сегодня"   :value="fmt(stats.public_users?.today)"     icon="✨" color="green" />
            <MetricCard title="За эту неделю"   :value="fmt(stats.public_users?.week)"      icon="📈" color="indigo" />
            <MetricCard title="Средний скоринг" :value="(stats.avg_score ?? 0) + '%'"        icon="🎯" color="amber" />
        </div>

        <!-- Метрики — строка 2: лиды -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <MetricCard title="Лидов всего"    :value="fmt(stats.leads?.total)"      icon="🎯" color="purple" />
            <MetricCard title="Новых лидов"    :value="fmt(stats.leads?.new)"        icon="🆕" color="sky" />
            <MetricCard title="Назначено"      :value="fmt(stats.leads?.assigned)"   icon="📋" color="teal" />
            <MetricCard title="Конвертировано" :value="fmt(stats.leads?.converted)"  icon="✅" color="green" />
        </div>

        <!-- Метрики — строка 3: агентства + финансы -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <MetricCard title="Агентств"       :value="fmt(stats.agencies?.total)"    icon="🏢" color="blue" />
            <MetricCard title="Активных"       :value="fmt(stats.agencies?.active)"   icon="🟢" color="green" />
            <MetricCard title="Выручка (USD)"  :value="'$' + fmt(stats.revenue?.total)"   icon="💰" color="amber" />
            <MetricCard title="В этом месяце"  :value="'$' + fmt(stats.revenue?.this_month)" icon="📅" color="indigo" />
        </div>

        <!-- Нижние блоки: популярные страны + последние агентства -->
        <div class="grid lg:grid-cols-2 gap-6">

            <!-- Топ стран -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h3 class="font-bold text-gray-800 mb-4">Популярные страны</h3>
                <div v-if="!stats.top_countries?.length" class="text-sm text-gray-400">Данных пока нет</div>
                <div v-else class="space-y-3">
                    <div v-for="c in stats.top_countries" :key="c.country_code"
                        class="flex items-center gap-3">
                        <span class="text-lg">{{ flag(c.country_code) }}</span>
                        <span class="text-sm text-gray-700 flex-1">{{ c.country_code }}</span>
                        <span class="text-sm font-bold text-[#0A1F44]">{{ c.count }} лидов</span>
                    </div>
                </div>
            </div>

            <!-- Последние агентства -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h3 class="font-bold text-gray-800 mb-4">Последние агентства</h3>
                <div v-if="!stats.recent_agencies?.length" class="text-sm text-gray-400">Нет агентств</div>
                <div v-else class="space-y-3">
                    <div v-for="a in stats.recent_agencies" :key="a.id"
                        class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#0A1F44]/10 rounded-lg flex items-center justify-center
                                    text-xs font-bold text-[#0A1F44]">
                            {{ a.name?.[0] ?? '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-800 truncate">{{ a.name }}</div>
                            <div class="text-xs text-gray-400">{{ a.plan }}</div>
                        </div>
                        <span :class="a.is_active ? 'text-green-600 bg-green-50' : 'text-red-500 bg-red-50'"
                            class="text-xs px-2 py-0.5 rounded-full font-medium">
                            {{ a.is_active ? 'Активно' : 'Заблок.' }}
                        </span>
                    </div>
                </div>
            </div>

        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index';

const stats   = ref({});
const loading = ref(true);

const flagMap = {
    DE: '🇩🇪', ES: '🇪🇸', FR: '🇫🇷', IT: '🇮🇹', PL: '🇵🇱',
    CZ: '🇨🇿', GB: '🇬🇧', US: '🇺🇸', CA: '🇨🇦', KR: '🇰🇷', AE: '🇦🇪',
};
function flag(code) { return flagMap[code] ?? '🌍'; }
function fmt(n) { return (n ?? 0).toLocaleString('ru-RU'); }

// Инлайн компонент MetricCard
const MetricCard = {
    name: 'MetricCard',
    props: ['title', 'value', 'icon', 'color'],
    template: `
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="text-xs text-gray-400 leading-tight">{{ title }}</div>
                <span class="text-xl">{{ icon }}</span>
            </div>
            <div class="text-2xl font-bold text-[#0A1F44]">{{ value ?? '—' }}</div>
        </div>
    `,
};

onMounted(async () => {
    try {
        const { data } = await api.get('/owner/dashboard');
        stats.value = data.data;
    } finally {
        loading.value = false;
    }
});
</script>
