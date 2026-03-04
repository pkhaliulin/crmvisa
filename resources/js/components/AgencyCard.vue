<template>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:border-[#1BA97F]/30 transition-colors">
        <div class="flex items-start gap-3">
            <!-- Logo -->
            <div class="w-12 h-12 rounded-xl border border-gray-100 flex items-center justify-center bg-gray-50 shrink-0 overflow-hidden">
                <img v-if="agency.logo_url" :src="agency.logo_url" :alt="agency.name" class="w-full h-full object-cover">
                <span v-else class="text-lg font-bold text-gray-300">{{ agency.name?.[0]?.toUpperCase() }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-bold text-[#0A1F44] text-sm">{{ agency.name }}</span>
                    <span v-if="agency.is_verified"
                        class="flex items-center gap-0.5 text-[10px] text-[#1BA97F] bg-[#1BA97F]/10 px-1.5 py-0.5 rounded-full shrink-0">
                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-400 flex-wrap">
                    <span v-if="agency.city">{{ agency.city }}</span>
                    <span v-if="agency.rating" class="flex items-center gap-0.5">
                        <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ Number(agency.rating).toFixed(1) }}
                        <span v-if="agency.reviews_count" class="text-gray-300">({{ agency.reviews_count }})</span>
                    </span>
                    <span v-if="agency.experience_years">{{ agency.experience_years }} {{ $t('common.years') }}</span>
                </div>
            </div>
        </div>

        <!-- Package -->
        <div v-if="agency.package" class="mt-3 p-3 bg-gray-50 rounded-xl">
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ agency.package.name }}</span>
                <span class="text-sm font-bold text-[#0A1F44]">
                    {{ agency.package.price }} {{ agency.package.currency }}
                </span>
            </div>
            <div v-if="agency.package.processing_days" class="text-xs text-gray-400 mt-0.5">
                {{ agency.package.processing_days }} {{ $t('common.days') }}
            </div>
        </div>

        <!-- Select button -->
        <button @click="$emit('select', agency)"
            class="w-full mt-3 py-2.5 bg-[#1BA97F] hover:bg-[#17956f] text-white text-sm font-semibold rounded-xl transition-colors">
            {{ $t('agencySelection.selectBtn') }}
        </button>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
defineProps({ agency: { type: Object, required: true } });
defineEmits(['select']);
</script>
