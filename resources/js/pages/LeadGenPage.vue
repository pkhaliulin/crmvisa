<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ t('crm.leadgen.title') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('crm.leadgen.subtitle') }}</p>
      </div>
    </div>

    <!-- Описание -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5">
      <h3 class="font-semibold text-blue-900 text-sm">{{ t('crm.leadgen.howItWorks') }}</h3>
      <p class="text-sm text-blue-800 mt-2">{{ t('crm.leadgen.howItWorksDesc') }}</p>
    </div>

    <!-- Источники лидогенерации -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="src in sources" :key="src.key"
        class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 hover:shadow-sm transition-all">
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-lg" :class="src.bgClass">
            {{ src.icon }}
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-gray-900 text-sm">{{ src.name }}</h3>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ src.desc }}</p>
            <div class="mt-3 space-y-1.5">
              <div v-for="(step, i) in src.steps" :key="i" class="flex items-start gap-2 text-xs text-gray-600">
                <span class="w-4 h-4 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center shrink-0 text-[10px] font-bold mt-0.5">{{ i + 1 }}</span>
                <span>{{ step }}</span>
              </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full" :class="difficultyClass(src.difficulty)">
                {{ t(`crm.leadgen.difficulty.${src.difficulty}`) }}
              </span>
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">
                {{ t(`crm.leadgen.type.${src.type}`) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- API интеграция -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-900 text-sm mb-2">{{ t('crm.leadgen.apiTitle') }}</h3>
      <p class="text-sm text-gray-600">{{ t('crm.leadgen.apiDesc') }}</p>
      <div class="mt-3 bg-gray-50 rounded-lg p-4 font-mono text-xs text-gray-700 overflow-x-auto">
        POST /api/v1/public/leads<br>
        { "name": "...", "phone": "+998...", "country_code": "DE", "source": "instagram" }
      </div>
      <p class="text-xs text-gray-500 mt-2">{{ t('crm.leadgen.apiHint') }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

function difficultyClass(d) {
  return d === 'easy' ? 'bg-green-50 text-green-600'
    : d === 'medium' ? 'bg-amber-50 text-amber-600'
    : 'bg-red-50 text-red-600';
}

const sources = computed(() => [
  {
    key: 'instagram',
    name: 'Instagram',
    icon: '\u{1F4F7}',
    bgClass: 'bg-pink-50',
    desc: t('crm.leadgen.src.instagram_desc'),
    steps: [
      t('crm.leadgen.src.instagram_s1'),
      t('crm.leadgen.src.instagram_s2'),
      t('crm.leadgen.src.instagram_s3'),
      t('crm.leadgen.src.instagram_s4'),
    ],
    difficulty: 'medium',
    type: 'paid',
  },
  {
    key: 'facebook',
    name: 'Facebook / Lead Ads',
    icon: '\u{1F4E2}',
    bgClass: 'bg-blue-50',
    desc: t('crm.leadgen.src.facebook_desc'),
    steps: [
      t('crm.leadgen.src.facebook_s1'),
      t('crm.leadgen.src.facebook_s2'),
      t('crm.leadgen.src.facebook_s3'),
      t('crm.leadgen.src.facebook_s4'),
    ],
    difficulty: 'medium',
    type: 'paid',
  },
  {
    key: 'telegram',
    name: 'Telegram-бот',
    icon: '\u{2708}',
    bgClass: 'bg-sky-50',
    desc: t('crm.leadgen.src.telegram_desc'),
    steps: [
      t('crm.leadgen.src.telegram_s1'),
      t('crm.leadgen.src.telegram_s2'),
      t('crm.leadgen.src.telegram_s3'),
    ],
    difficulty: 'easy',
    type: 'free',
  },
  {
    key: 'google_ads',
    name: 'Google Ads',
    icon: '\u{1F50D}',
    bgClass: 'bg-yellow-50',
    desc: t('crm.leadgen.src.google_ads_desc'),
    steps: [
      t('crm.leadgen.src.google_ads_s1'),
      t('crm.leadgen.src.google_ads_s2'),
      t('crm.leadgen.src.google_ads_s3'),
    ],
    difficulty: 'hard',
    type: 'paid',
  },
  {
    key: 'website',
    name: t('crm.leadgen.src.website_name'),
    icon: '\u{1F310}',
    bgClass: 'bg-indigo-50',
    desc: t('crm.leadgen.src.website_desc'),
    steps: [
      t('crm.leadgen.src.website_s1'),
      t('crm.leadgen.src.website_s2'),
      t('crm.leadgen.src.website_s3'),
    ],
    difficulty: 'medium',
    type: 'free',
  },
  {
    key: 'referral',
    name: t('crm.leadgen.src.referral_name'),
    icon: '\u{1F91D}',
    bgClass: 'bg-green-50',
    desc: t('crm.leadgen.src.referral_desc'),
    steps: [
      t('crm.leadgen.src.referral_s1'),
      t('crm.leadgen.src.referral_s2'),
      t('crm.leadgen.src.referral_s3'),
    ],
    difficulty: 'easy',
    type: 'free',
  },
  {
    key: 'tiktok',
    name: 'TikTok',
    icon: '\u{1F3AC}',
    bgClass: 'bg-gray-50',
    desc: t('crm.leadgen.src.tiktok_desc'),
    steps: [
      t('crm.leadgen.src.tiktok_s1'),
      t('crm.leadgen.src.tiktok_s2'),
      t('crm.leadgen.src.tiktok_s3'),
    ],
    difficulty: 'medium',
    type: 'free',
  },
  {
    key: 'youtube',
    name: 'YouTube',
    icon: '\u{25B6}',
    bgClass: 'bg-red-50',
    desc: t('crm.leadgen.src.youtube_desc'),
    steps: [
      t('crm.leadgen.src.youtube_s1'),
      t('crm.leadgen.src.youtube_s2'),
      t('crm.leadgen.src.youtube_s3'),
    ],
    difficulty: 'hard',
    type: 'free',
  },
  {
    key: 'partner',
    name: t('crm.leadgen.src.partner_name'),
    icon: '\u{1F3E2}',
    bgClass: 'bg-purple-50',
    desc: t('crm.leadgen.src.partner_desc'),
    steps: [
      t('crm.leadgen.src.partner_s1'),
      t('crm.leadgen.src.partner_s2'),
      t('crm.leadgen.src.partner_s3'),
    ],
    difficulty: 'easy',
    type: 'free',
  },
  {
    key: 'marketplace',
    name: 'VisaBor Marketplace',
    icon: '\u{1F680}',
    bgClass: 'bg-emerald-50',
    desc: t('crm.leadgen.src.marketplace_desc'),
    steps: [
      t('crm.leadgen.src.marketplace_s1'),
      t('crm.leadgen.src.marketplace_s2'),
      t('crm.leadgen.src.marketplace_s3'),
    ],
    difficulty: 'easy',
    type: 'free',
  },
]);
</script>
