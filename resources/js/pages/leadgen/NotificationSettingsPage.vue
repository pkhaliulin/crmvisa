<template>
  <div class="space-y-6">
    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Header -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h1 class="text-xl font-bold text-gray-900">{{ t('crm.leadgen.notifications.title') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('crm.leadgen.notifications.subtitle') }}</p>
      </div>

      <!-- Channel legend -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex flex-wrap gap-3">
          <div v-for="ch in availableChannels" :key="ch"
            class="flex items-center gap-1.5 text-xs">
            <div class="w-2 h-2 rounded-full" :class="channelStatus[ch] === 'active' ? 'bg-green-500' : 'bg-amber-400'"></div>
            <span class="text-gray-700">{{ t(`crm.leadgen.notifications.channel_${ch}`) }}</span>
            <span v-if="channelStatus[ch] === 'stub'" class="text-[10px] text-amber-600 font-medium px-1.5 py-0.5 bg-amber-50 rounded">
              {{ t('crm.leadgen.notifications.stub') }}
            </span>
          </div>
        </div>
      </div>

      <!-- Settings list -->
      <div class="space-y-3">
        <div v-for="setting in settings" :key="setting.event_type"
          class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-start justify-between gap-4 mb-3">
            <div>
              <h3 class="text-sm font-semibold text-gray-900">
                {{ t(`crm.leadgen.notifications.event_${setting.event_type.replace('.', '_')}`) }}
              </h3>
              <span class="text-[10px] text-gray-400 font-mono">{{ setting.event_type }}</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="setting.is_enabled" @change="saveSetting(setting)" class="sr-only peer">
              <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <div v-if="setting.is_enabled" class="space-y-3">
            <!-- Channels -->
            <div>
              <p class="text-xs text-gray-500 mb-1.5">{{ t('crm.leadgen.notifications.channels') }}</p>
              <div class="flex flex-wrap gap-1.5">
                <button v-for="ch in availableChannels" :key="ch"
                  @click="toggleChannel(setting, ch)"
                  class="text-xs px-2.5 py-1 rounded-full border transition-colors"
                  :class="setting.channels.includes(ch)
                    ? 'bg-blue-50 border-blue-300 text-blue-700'
                    : 'bg-gray-50 border-gray-200 text-gray-400'">
                  {{ t(`crm.leadgen.notifications.channel_${ch}`) }}
                </button>
              </div>
            </div>

            <!-- Recipients -->
            <div>
              <p class="text-xs text-gray-500 mb-1.5">{{ t('crm.leadgen.notifications.recipients') }}</p>
              <div class="flex flex-wrap gap-1.5">
                <button v-for="rec in availableRecipients" :key="rec"
                  @click="toggleRecipient(setting, rec)"
                  class="text-xs px-2.5 py-1 rounded-full border transition-colors"
                  :class="setting.recipients.includes(rec)
                    ? 'bg-green-50 border-green-300 text-green-700'
                    : 'bg-gray-50 border-gray-200 text-gray-400'">
                  {{ t(`crm.leadgen.notifications.recipient_${rec}`) }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';

const { t } = useI18n();

const loading = ref(true);
const settings = ref([]);
const availableChannels = ref([]);
const availableRecipients = ref([]);
const channelStatus = ref({});

function toggleChannel(setting, ch) {
  const idx = setting.channels.indexOf(ch);
  if (idx >= 0) {
    setting.channels.splice(idx, 1);
  } else {
    setting.channels.push(ch);
  }
  saveSetting(setting);
}

function toggleRecipient(setting, rec) {
  const idx = setting.recipients.indexOf(rec);
  if (idx >= 0) {
    setting.recipients.splice(idx, 1);
  } else {
    setting.recipients.push(rec);
  }
  saveSetting(setting);
}

let saveTimeouts = {};

function saveSetting(setting) {
  // Debounce saves
  if (saveTimeouts[setting.event_type]) {
    clearTimeout(saveTimeouts[setting.event_type]);
  }
  saveTimeouts[setting.event_type] = setTimeout(async () => {
    try {
      await api.put(`/notifications/settings/${setting.event_type}`, {
        channels: setting.channels,
        recipients: setting.recipients,
        is_enabled: setting.is_enabled,
      });
    } catch {
      // silent
    }
  }, 500);
}

onMounted(async () => {
  try {
    const { data } = await api.get('/notifications/settings');
    const payload = data?.data || data;
    settings.value = payload.settings || [];
    availableChannels.value = payload.available_channels || [];
    availableRecipients.value = payload.available_recipients || [];
    channelStatus.value = payload.channel_status || {};
  } catch {
    settings.value = [];
  } finally {
    loading.value = false;
  }
});
</script>
