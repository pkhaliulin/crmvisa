<template>
  <div class="space-y-4">
    <div>
      <h1 class="text-xl font-bold text-gray-900">{{ t('crm.settings.title') }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ t('crm.settings.subtitle') }}</p>
    </div>

    <!-- Вкладки -->
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
      <button v-for="tab in tabs" :key="tab.key"
        @click="switchTab(tab.key)"
        :class="['px-4 py-1.5 text-sm rounded-lg transition-all font-medium flex items-center gap-2',
          activeTab === tab.key
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-500 hover:text-gray-700']">
        <svg v-if="tab.key === 'agency'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
        </svg>
        <svg v-else-if="tab.key === 'profile'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
        </svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
        </svg>
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <div v-else-if="errorMsg && !form.description && !form.email" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
      <p class="text-red-600 font-medium">{{ errorMsg }}</p>
    </div>

    <template v-else>
      <!-- ==================== ВКЛАДКА: Агентство ==================== -->
      <template v-if="activeTab === 'agency'">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <!-- Левая колонка: Основное -->
          <div class="lg:col-span-2 space-y-4">
            <!-- Общая информация -->
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <div class="px-5 py-3 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                  </svg>
                </div>
                <h2 class="font-semibold text-gray-700 text-sm">{{ t('crm.settings.generalInfo') }}</h2>
              </div>
              <div class="p-5 space-y-4">
                <AppTextarea v-model="form.description" :label="t('crm.settings.descRu')"
                  :placeholder="t('crm.settings.descRuPlaceholder')" :maxlength="1000" :rows="3"
                  :hint="t('crm.settings.descRuHint')" />
                <AppTextarea v-model="form.description_uz" :label="t('crm.settings.descUz')"
                  :placeholder="t('crm.settings.descUzPlaceholder')" :maxlength="1000" :rows="3"
                  :hint="t('crm.settings.descUzHint')" />

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
                        </svg>
                        {{ t('crm.settings.website') }}
                      </span>
                    </label>
                    <input v-model="form.website_url" type="url" placeholder="https://..."
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                        </svg>
                        {{ t('crm.settings.city') }}
                      </span>
                    </label>
                    <input v-model="form.city" type="text" :placeholder="t('crm.settings.cityPlaceholder')"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ t('crm.settings.experience') }}
                      </span>
                    </label>
                    <input v-model.number="form.experience_years" type="number" min="0" max="100" placeholder="5"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                </div>
              </div>
            </section>

            <!-- Контакты -->
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <div class="px-5 py-3 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-green-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                  </svg>
                </div>
                <h2 class="font-semibold text-gray-700 text-sm">{{ t('crm.settings.contacts') }}</h2>
              </div>
              <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                        {{ t('crm.settings.phone') }}
                      </span>
                    </label>
                    <input v-model="form.phone" type="tel" :placeholder="t('crm.settings.phonePlaceholder')"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                        {{ t('crm.settings.email') }}
                      </span>
                    </label>
                    <input v-model="form.email" type="email" :placeholder="t('crm.settings.emailPlaceholder')"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21"/>
                      </svg>
                      {{ t('crm.settings.address') }}
                    </span>
                  </label>
                  <input v-model="form.address" type="text" :placeholder="t('crm.settings.addressPlaceholder')"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.lat') }}</label>
                    <input v-model="form.latitude" type="text" placeholder="41.2995"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors font-mono text-xs" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.lng') }}</label>
                    <input v-model="form.longitude" type="text" placeholder="69.2401"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors font-mono text-xs" />
                  </div>
                </div>
                <p class="text-xs text-gray-400 flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/>
                  </svg>
                  {{ t('crm.settings.coordsHint') }}
                </p>
              </div>
            </section>
          </div>

          <!-- Правая колонка: Команда + Направления -->
          <div class="space-y-4">
            <!-- Команда -->
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <div class="px-5 py-3 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-purple-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                  </svg>
                </div>
                <h2 class="font-semibold text-gray-700 text-sm">{{ t('crm.settings.team') }}</h2>
              </div>
              <div class="p-5 space-y-4">
                <!-- Видимость заявок -->
                <div class="p-3 rounded-lg border border-gray-100 bg-gray-50/50">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                      <p class="text-sm font-medium text-gray-700">{{ t('crm.settings.managersCanSeeAll') }}</p>
                      <p class="text-xs text-gray-400 mt-0.5">{{ t('crm.settings.managersOnlyOwn') }}</p>
                    </div>
                    <button @click="form.managers_see_all_cases = !form.managers_see_all_cases"
                      :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none shrink-0',
                        form.managers_see_all_cases ? 'bg-blue-600' : 'bg-gray-300']">
                      <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200',
                        form.managers_see_all_cases ? 'translate-x-6' : 'translate-x-1']" />
                    </button>
                  </div>
                </div>

                <!-- Распределение лидов -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    <span class="flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                      </svg>
                      {{ t('crm.settings.leadDistribution') }}
                    </span>
                  </label>
                  <div class="space-y-1.5">
                    <label v-for="opt in leadModes" :key="opt.value"
                      class="flex items-center gap-3 p-2.5 rounded-lg border cursor-pointer transition-all"
                      :class="form.lead_assignment_mode === opt.value
                        ? 'border-blue-300 bg-blue-50/50'
                        : 'border-gray-100 hover:border-gray-200'">
                      <input type="radio" v-model="form.lead_assignment_mode" :value="opt.value"
                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
                      <div>
                        <p class="text-sm font-medium text-gray-700">{{ opt.label }}</p>
                        <p class="text-xs text-gray-400">{{ opt.hint }}</p>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
            </section>

            <!-- Рабочие направления -->
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <div class="px-5 py-3 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-orange-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
                  </svg>
                </div>
                <h2 class="font-semibold text-gray-700 text-sm">{{ t('crm.settings.directions') }}</h2>
              </div>
              <div class="p-5">
                <p class="text-sm text-gray-500">
                  {{ t('crm.settings.directionsManage') }}
                  <router-link :to="{ name: 'countries' }" class="text-blue-600 font-medium hover:underline inline-flex items-center gap-1">
                    {{ t('crm.settings.countries') }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                  </router-link>
                </p>
              </div>
            </section>
          </div>
        </div>

        <!-- Кнопка Сохранить -->
        <div class="flex items-center justify-between bg-white rounded-xl border border-gray-200 px-5 py-3">
          <div>
            <p v-if="successMsg" class="text-sm text-green-600 font-medium flex items-center gap-1.5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
              </svg>
              {{ successMsg }}
            </p>
            <p v-if="errorMsg" class="text-sm text-red-600 flex items-center gap-1.5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
              </svg>
              {{ errorMsg }}
            </p>
          </div>
          <button @click="saveAgency" :disabled="saving"
            class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center gap-2">
            <svg v-if="!saving" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <div v-else class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            {{ saving ? t('crm.settings.saving') : t('crm.settings.save') }}
          </button>
        </div>
      </template>

      <!-- ==================== ВКЛАДКА: Личный профиль ==================== -->
      <template v-if="activeTab === 'profile'">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <!-- Левая колонка: Профиль-карточка -->
          <div class="space-y-4">
            <!-- Карточка профиля -->
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <div class="h-20 bg-gradient-to-br from-[#0A1F44] to-[#1a3a6e]"></div>
              <div class="px-5 pb-5 -mt-8">
                <div class="w-16 h-16 rounded-full bg-white p-1 mb-3">
                  <div class="w-full h-full rounded-full bg-gradient-to-br from-[#0A1F44] to-[#1a3a6e] flex items-center justify-center text-white font-bold text-xl">
                    {{ profile.name?.[0]?.toUpperCase() ?? '?' }}
                  </div>
                </div>
                <p class="font-semibold text-gray-900 text-lg">{{ profile.name }}</p>
                <span class="inline-flex items-center gap-1 mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="profile.role === 'owner'
                    ? 'bg-purple-50 text-purple-700'
                    : profile.role === 'manager'
                      ? 'bg-blue-50 text-blue-700'
                      : 'bg-gray-100 text-gray-600'">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                  </svg>
                  {{ roleLabel }}
                </span>
                <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                  </svg>
                  {{ profile.email }}
                </p>
              </div>
            </section>
          </div>

          <!-- Правая колонка: Формы -->
          <div class="lg:col-span-2 space-y-4">
            <!-- Контактные данные -->
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <div class="px-5 py-3 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-green-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                  </svg>
                </div>
                <h2 class="font-semibold text-gray-700 text-sm">{{ t('crm.settings.personalContacts') }}</h2>
              </div>
              <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <AppInput v-model="profile.email" label="Email" type="email" disabled :hint="t('crm.settings.emailReadonly')" />
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                        {{ t('crm.settings.phone') }}
                      </span>
                    </label>
                    <input v-model="profile.phone" type="tel" :placeholder="t('crm.settings.phonePlaceholder')"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-[#229ED9]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                      </svg>
                      {{ t('crm.settings.telegram') }}
                    </span>
                  </label>
                  <div class="flex items-center">
                    <span class="px-3 py-2 bg-gray-50 border border-r-0 border-gray-200 rounded-l-lg text-sm text-gray-400">@</span>
                    <input v-model="profile.telegram_username" type="text" placeholder="username"
                      class="flex-1 border border-gray-200 rounded-r-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                </div>
              </div>
            </section>

            <!-- Смена пароля -->
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <div class="px-5 py-3 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-red-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                  </svg>
                </div>
                <h2 class="font-semibold text-gray-700 text-sm">{{ t('crm.settings.changePassword') }}</h2>
              </div>
              <div class="p-5 space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.currentPassword') }}</label>
                  <div class="relative">
                    <input v-model="passwords.current" :type="showCurrentPw ? 'text' : 'password'"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 pr-10 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                    <button type="button" @click="showCurrentPw = !showCurrentPw"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                      <svg v-if="!showCurrentPw" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      </svg>
                      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                      </svg>
                    </button>
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.newPassword') }}</label>
                    <div class="relative">
                      <input v-model="passwords.new_password" :type="showNewPw ? 'text' : 'password'" :placeholder="t('crm.settings.newPasswordPlaceholder')"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 pr-10 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                      <button type="button" @click="showNewPw = !showNewPw"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.repeatPassword') }}</label>
                    <input v-model="passwords.confirm" type="password"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                </div>

                <!-- Индикатор надежности пароля -->
                <div v-if="passwords.new_password" class="space-y-1.5">
                  <div class="flex gap-1">
                    <div v-for="i in 4" :key="i" class="h-1 flex-1 rounded-full transition-colors"
                      :class="passwordStrength >= i
                        ? passwordStrength <= 1 ? 'bg-red-400' : passwordStrength <= 2 ? 'bg-orange-400' : passwordStrength <= 3 ? 'bg-yellow-400' : 'bg-green-500'
                        : 'bg-gray-200'"></div>
                  </div>
                  <p class="text-xs" :class="passwordStrength <= 1 ? 'text-red-500' : passwordStrength <= 2 ? 'text-orange-500' : passwordStrength <= 3 ? 'text-yellow-600' : 'text-green-600'">
                    {{ passwordStrengthLabel }}
                  </p>
                </div>

                <p v-if="passwordError" class="text-xs text-red-500 bg-red-50 p-2 rounded-lg flex items-center gap-1">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                  </svg>
                  {{ passwordError }}
                </p>
                <p v-if="passwordSuccess" class="text-xs text-green-600 bg-green-50 p-2 rounded-lg flex items-center gap-1">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                  </svg>
                  {{ passwordSuccess }}
                </p>
              </div>
            </section>

            <!-- Кнопка Сохранить -->
            <div class="flex items-center justify-between bg-white rounded-xl border border-gray-200 px-5 py-3">
              <div>
                <p v-if="profileSuccess" class="text-sm text-green-600 font-medium flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                  </svg>
                  {{ profileSuccess }}
                </p>
                <p v-if="profileError" class="text-sm text-red-600 flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                  </svg>
                  {{ profileError }}
                </p>
              </div>
              <button @click="saveProfile" :disabled="savingProfile"
                class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                <svg v-if="!savingProfile" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                <div v-else class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                {{ savingProfile ? t('crm.settings.saving') : t('crm.settings.saveProfile') }}
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- ==================== ВКЛАДКА: Возможности тарифа ==================== -->
      <template v-if="activeTab === 'features'">
        <div v-if="featuresLoading" class="flex items-center justify-center py-20">
          <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
        </div>

        <div v-else-if="featuresError" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
          <p class="text-red-600 font-medium">{{ featuresError }}</p>
        </div>

        <div v-else class="space-y-4">
          <!-- Сетка карточек функций -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="feat in featureCards" :key="feat.key"
              class="bg-white rounded-xl border overflow-hidden transition-all"
              :class="feat.statusClass">

              <!-- Заголовок карточки -->
              <div class="px-5 py-4 flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                  :class="feat.available ? feat.iconBg : 'bg-gray-100'">
                  <svg class="w-5 h-5" :class="feat.available ? feat.iconColor : 'text-gray-400'" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="feat.icon" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-sm" :class="feat.available ? 'text-gray-900' : 'text-gray-400'">{{ feat.name }}</h3>
                  <p class="text-xs mt-0.5" :class="feat.available ? 'text-gray-500' : 'text-gray-400'">{{ feat.desc }}</p>
                </div>
              </div>

              <!-- Статус -->
              <div class="px-5 pb-4">
                <!-- Заблокировано -->
                <div v-if="!feat.available" class="space-y-3">
                  <div class="flex items-center gap-2 text-sm text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    {{ t('crm.settings.featureLocked') }}
                  </div>
                  <router-link :to="{ name: 'billing' }"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:text-blue-700 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/>
                    </svg>
                    {{ t('crm.settings.upgradeToUnlock', { plan: 'Pro' }) }}
                  </router-link>
                </div>

                <!-- Активно (без требований или все выполнены) -->
                <div v-else-if="feat.activated" class="flex items-center gap-2">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    {{ t('crm.settings.featureActive') }}
                  </span>
                </div>

                <!-- Требуется настройка -->
                <div v-else class="space-y-3">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-orange-50 text-orange-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                    </svg>
                    {{ t('crm.settings.featureNeedsSetup') }}
                  </span>

                  <!-- Чеклист требований -->
                  <div v-if="Object.keys(feat.requirements).length" class="space-y-1.5">
                    <div v-for="(met, reqKey) in feat.requirements" :key="reqKey"
                      class="flex items-center gap-2 text-xs">
                      <svg v-if="met" class="w-3.5 h-3.5 text-green-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                      </svg>
                      <svg v-else class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                      </svg>
                      <span :class="met ? 'text-gray-500 line-through' : 'text-gray-700'">
                        {{ requirementLabel(reqKey) }}
                      </span>
                      <span class="ml-auto text-xs" :class="met ? 'text-green-600' : 'text-gray-400'">
                        {{ met ? t('crm.settings.filled') : t('crm.settings.notFilled') }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Действия для активных функций -->
                <div v-if="feat.available && feat.activated" class="mt-3">
                  <!-- Аналитика -->
                  <router-link v-if="feat.key === 'analytics'" :to="{ name: 'reports' }"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:text-blue-700 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z"/>
                    </svg>
                    {{ t('crm.settings.goToReports') }}
                  </router-link>

                  <!-- Поддержка -->
                  <div v-if="feat.key === 'priority_support'" class="space-y-1 text-xs text-gray-500">
                    <p>{{ t('crm.settings.supportEmail') }}</p>
                    <p>{{ t('crm.settings.supportTelegram') }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- API-ключ (если API доступен) -->
          <template v-if="featureStatus?.api_access?.available">
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <div class="px-5 py-3 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-indigo-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                  </svg>
                </div>
                <h2 class="font-semibold text-gray-700 text-sm">{{ t('crm.settings.apiKeySection') }}</h2>
              </div>
              <div class="p-5 space-y-4">
                <div v-if="generatedApiKey" class="bg-green-50 border border-green-200 rounded-lg p-4 space-y-2">
                  <p class="text-sm font-medium text-green-800">{{ t('crm.settings.apiKeyGenerated') }}</p>
                  <code class="block text-xs bg-white border border-green-200 rounded px-3 py-2 font-mono text-gray-800 break-all">{{ generatedApiKey }}</code>
                  <p class="text-xs text-orange-600">{{ t('crm.settings.apiKeyWarning') }}</p>
                </div>
                <button @click="generateApiKey" :disabled="generatingKey"
                  class="px-4 py-2 text-sm font-medium rounded-lg transition-colors flex items-center gap-2"
                  :class="apiKeyInfo?.has_key
                    ? 'bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200'
                    : 'bg-blue-600 text-white hover:bg-blue-700'">
                  <div v-if="generatingKey" class="w-4 h-4 border-2 border-current/30 border-t-current rounded-full animate-spin"></div>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                  </svg>
                  {{ apiKeyInfo?.has_key ? t('crm.settings.regenerateApiKey') : t('crm.settings.generateApiKey') }}
                </button>
              </div>
            </section>
          </template>

          <!-- White-label настройки (если доступно) -->
          <template v-if="featureStatus?.white_label?.available">
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <div class="px-5 py-3 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-violet-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/>
                  </svg>
                </div>
                <h2 class="font-semibold text-gray-700 text-sm">{{ t('crm.settings.whiteLabel') }}</h2>
              </div>
              <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.logoUrl') }}</label>
                    <input v-model="brandingForm.logo_url" type="url" placeholder="https://..."
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.faviconUrl') }}</label>
                    <input v-model="brandingForm.favicon_url" type="url" placeholder="https://..."
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.primaryColor') }}</label>
                    <div class="flex items-center gap-2">
                      <input v-model="brandingForm.primary_color" type="color"
                        class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5" />
                      <input v-model="brandingForm.primary_color" type="text" placeholder="#0A1F44" maxlength="7"
                        class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors font-mono" />
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.secondaryColor') }}</label>
                    <div class="flex items-center gap-2">
                      <input v-model="brandingForm.secondary_color" type="color"
                        class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5" />
                      <input v-model="brandingForm.secondary_color" type="text" placeholder="#1BA97F" maxlength="7"
                        class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors font-mono" />
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.telegramBotToken') }}</label>
                    <input v-model="brandingForm.telegram_bot_token" type="text" placeholder="123456:ABC-DEF..."
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors font-mono text-xs" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.telegramBotUsername') }}</label>
                    <div class="flex items-center">
                      <span class="px-3 py-2 bg-gray-50 border border-r-0 border-gray-200 rounded-l-lg text-sm text-gray-400">@</span>
                      <input v-model="brandingForm.telegram_bot_username" type="text" placeholder="my_visa_bot"
                        class="flex-1 border border-gray-200 rounded-r-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                    </div>
                  </div>
                </div>

                <!-- Кастомный домен (если доступно) -->
                <div v-if="featureStatus?.custom_domain?.available">
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.settings.customDomainInput') }}</label>
                  <input v-model="brandingForm.custom_domain" type="text" placeholder="visa.myagency.com"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  <p class="text-xs text-gray-400 mt-1">{{ t('crm.settings.customDomainHint') }}</p>
                </div>

                <!-- Кнопка сохранить брендинг -->
                <div class="flex items-center justify-between pt-2">
                  <div>
                    <p v-if="brandingSuccess" class="text-sm text-green-600 font-medium flex items-center gap-1.5">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                      </svg>
                      {{ brandingSuccess }}
                    </p>
                    <p v-if="brandingError" class="text-sm text-red-600 flex items-center gap-1.5">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                      </svg>
                      {{ brandingError }}
                    </p>
                  </div>
                  <button @click="saveBranding" :disabled="savingBranding"
                    class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                    <svg v-if="!savingBranding" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    <div v-else class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    {{ savingBranding ? t('crm.settings.saving') : t('crm.settings.saveBranding') }}
                  </button>
                </div>
              </div>
            </section>
          </template>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import AppInput from '@/components/AppInput.vue';
import AppTextarea from '@/components/AppTextarea.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const auth = useAuthStore();
const loading = ref(true);
const saving = ref(false);
const savingProfile = ref(false);
const successMsg = ref('');
const errorMsg = ref('');
const profileSuccess = ref('');
const profileError = ref('');
const passwordError = ref('');
const passwordSuccess = ref('');
const activeTab = ref('agency');
const showCurrentPw = ref(false);
const showNewPw = ref(false);

// Features tab state
const featuresLoading = ref(false);
const featuresError = ref('');
const featureStatus = ref(null);
const featuresLoaded = ref(false);
const apiKeyInfo = ref(null);
const generatedApiKey = ref('');
const generatingKey = ref(false);
const savingBranding = ref(false);
const brandingSuccess = ref('');
const brandingError = ref('');

const brandingForm = ref({
  logo_url: '',
  primary_color: '#0A1F44',
  secondary_color: '#1BA97F',
  favicon_url: '',
  telegram_bot_token: '',
  telegram_bot_username: '',
  custom_domain: '',
});

const tabs = computed(() => [
  { key: 'agency',   label: t('crm.settings.tabAgency') },
  { key: 'profile',  label: t('crm.settings.tabProfile') },
  { key: 'features', label: t('crm.settings.features') },
]);

const leadModes = computed(() => [
  { value: 'manual', label: t('crm.settings.leadModes.manual'), hint: t('crm.settings.leadModes.manualHint') },
  { value: 'round_robin', label: t('crm.settings.leadModes.round_robin'), hint: t('crm.settings.leadModes.round_robinHint') },
  { value: 'by_workload', label: t('crm.settings.leadModes.by_workload'), hint: t('crm.settings.leadModes.by_workloadHint') },
  { value: 'by_country', label: t('crm.settings.leadModes.by_country'), hint: t('crm.settings.leadModes.by_countryHint') },
]);

const roleLabel = computed(() => {
  if (profile.value.role === 'owner') return t('crm.settings.roleOwner');
  if (profile.value.role === 'manager') return t('crm.settings.roleManager');
  return profile.value.role;
});

const form = ref({
  description: '', description_uz: '', website_url: '', city: '',
  experience_years: null, address: '', managers_see_all_cases: false,
  lead_assignment_mode: 'manual', phone: '', email: '', latitude: '', longitude: '',
});

const profile = ref({
  name: '', email: '', phone: '', telegram_username: '', role: '',
});

const passwords = reactive({ current: '', new_password: '', confirm: '' });

const passwordStrength = computed(() => {
  const pw = passwords.new_password;
  if (!pw) return 0;
  let s = 0;
  if (pw.length >= 8) s++;
  if (/[A-Z]/.test(pw)) s++;
  if (/[0-9]/.test(pw)) s++;
  if (/[^A-Za-z0-9]/.test(pw)) s++;
  return s;
});

const passwordStrengthLabel = computed(() => {
  const labels = ['', t('crm.settings.strengthWeak'), t('crm.settings.strengthMedium'), t('crm.settings.strengthGood'), t('crm.settings.strengthStrong')];
  return labels[passwordStrength.value] || '';
});

// Feature icons and metadata
const featureCards = computed(() => {
  if (!featureStatus.value) return [];
  const fs = featureStatus.value;
  return [
    {
      key: 'marketplace',
      name: t('crm.settings.marketplace'),
      desc: t('crm.settings.marketplaceDesc'),
      icon: 'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z',
      iconBg: 'bg-emerald-50',
      iconColor: 'text-emerald-600',
      available: fs.marketplace?.available,
      activated: fs.marketplace?.activated,
      requirements: fs.marketplace?.requirements || {},
      statusClass: !fs.marketplace?.available ? 'border-gray-200 opacity-60' : fs.marketplace?.activated ? 'border-green-200' : 'border-orange-200',
    },
    {
      key: 'white_label',
      name: t('crm.settings.whiteLabel'),
      desc: t('crm.settings.whiteLabelDesc'),
      icon: 'M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42',
      iconBg: 'bg-violet-50',
      iconColor: 'text-violet-600',
      available: fs.white_label?.available,
      activated: fs.white_label?.activated,
      requirements: fs.white_label?.requirements || {},
      statusClass: !fs.white_label?.available ? 'border-gray-200 opacity-60' : fs.white_label?.activated ? 'border-green-200' : 'border-orange-200',
    },
    {
      key: 'api_access',
      name: t('crm.settings.apiAccess'),
      desc: t('crm.settings.apiAccessDesc'),
      icon: 'M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5',
      iconBg: 'bg-indigo-50',
      iconColor: 'text-indigo-600',
      available: fs.api_access?.available,
      activated: fs.api_access?.activated,
      requirements: fs.api_access?.requirements || {},
      statusClass: !fs.api_access?.available ? 'border-gray-200 opacity-60' : fs.api_access?.activated ? 'border-green-200' : 'border-orange-200',
    },
    {
      key: 'analytics',
      name: t('crm.settings.analyticsFeature'),
      desc: t('crm.settings.analyticsDesc'),
      icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
      iconBg: 'bg-blue-50',
      iconColor: 'text-blue-600',
      available: fs.analytics?.available,
      activated: fs.analytics?.activated,
      requirements: fs.analytics?.requirements || {},
      statusClass: !fs.analytics?.available ? 'border-gray-200 opacity-60' : fs.analytics?.activated ? 'border-green-200' : 'border-orange-200',
    },
    {
      key: 'custom_domain',
      name: t('crm.settings.customDomain'),
      desc: t('crm.settings.customDomainDesc'),
      icon: 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418',
      iconBg: 'bg-cyan-50',
      iconColor: 'text-cyan-600',
      available: fs.custom_domain?.available,
      activated: fs.custom_domain?.activated,
      requirements: fs.custom_domain?.requirements || {},
      statusClass: !fs.custom_domain?.available ? 'border-gray-200 opacity-60' : fs.custom_domain?.activated ? 'border-green-200' : 'border-orange-200',
    },
    {
      key: 'priority_support',
      name: t('crm.settings.prioritySupport'),
      desc: t('crm.settings.prioritySupportDesc'),
      icon: 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
      iconBg: 'bg-amber-50',
      iconColor: 'text-amber-600',
      available: fs.priority_support?.available,
      activated: fs.priority_support?.activated,
      requirements: fs.priority_support?.requirements || {},
      statusClass: !fs.priority_support?.available ? 'border-gray-200 opacity-60' : fs.priority_support?.activated ? 'border-green-200' : 'border-orange-200',
    },
  ];
});

const requirementLabelMap = computed(() => ({
  description: t('crm.settings.reqDescription'),
  work_countries: t('crm.settings.reqWorkCountries'),
  service_packages: t('crm.settings.reqServicePackages'),
  logo_url: t('crm.settings.reqLogoUrl'),
  telegram_bot_token: t('crm.settings.reqTelegramBotToken'),
  telegram_bot_username: t('crm.settings.reqTelegramBotUsername'),
  custom_domain: t('crm.settings.reqCustomDomain'),
  api_key: t('crm.settings.apiKeySection'),
}));

function requirementLabel(key) {
  return requirementLabelMap.value[key] || key;
}

function switchTab(key) {
  activeTab.value = key;
  if (key === 'features' && !featuresLoaded.value) {
    loadFeatureStatus();
  }
}

async function loadFeatureStatus() {
  featuresLoading.value = true;
  featuresError.value = '';
  try {
    const [statusRes, keyRes] = await Promise.all([
      api.get('/agency/feature-status'),
      api.get('/agency/api-key'),
    ]);
    featureStatus.value = statusRes.data?.data?.features || {};
    apiKeyInfo.value = keyRes.data?.data || {};
    featuresLoaded.value = true;

    // Pre-fill branding form from agency settings
    const settingsRes = await api.get('/agency/settings');
    const agencyData = settingsRes.data?.data || {};
    brandingForm.value = {
      logo_url: agencyData.logo_url || '',
      primary_color: agencyData.primary_color || '#0A1F44',
      secondary_color: agencyData.secondary_color || '#1BA97F',
      favicon_url: agencyData.favicon_url || '',
      telegram_bot_token: agencyData.telegram_bot_token || '',
      telegram_bot_username: agencyData.telegram_bot_username || '',
      custom_domain: agencyData.custom_domain || '',
    };
  } catch (e) {
    featuresError.value = e.response?.data?.message || t('crm.settings.featureStatusError');
  } finally {
    featuresLoading.value = false;
  }
}

async function generateApiKey() {
  generatingKey.value = true;
  try {
    const res = await api.post('/agency/api-key');
    generatedApiKey.value = res.data?.data?.api_key || '';
    apiKeyInfo.value = { has_key: true, generated_at: res.data?.data?.generated_at };
    // Reload feature status to update api_access activated state
    const statusRes = await api.get('/agency/feature-status');
    featureStatus.value = statusRes.data?.data?.features || {};
  } catch (e) {
    // silent
  } finally {
    generatingKey.value = false;
  }
}

async function saveBranding() {
  savingBranding.value = true;
  brandingSuccess.value = '';
  brandingError.value = '';
  try {
    const payload = Object.fromEntries(
      Object.entries(brandingForm.value).map(([k, v]) => [k, v === '' ? null : v])
    );
    await api.patch('/agency/branding', payload);
    brandingSuccess.value = t('crm.settings.brandingSaved');
    setTimeout(() => { brandingSuccess.value = ''; }, 3000);
    // Reload feature status
    const statusRes = await api.get('/agency/feature-status');
    featureStatus.value = statusRes.data?.data?.features || {};
  } catch (e) {
    brandingError.value = e.response?.data?.message || t('crm.settings.brandingSaveError');
  } finally {
    savingBranding.value = false;
  }
}

onMounted(async () => {
  try {
    const settingsRes = await api.get('/agency/settings');
    const data = settingsRes.data?.data || {};
    Object.keys(form.value).forEach(key => {
      if (data[key] !== undefined && data[key] !== null) form.value[key] = data[key];
    });

    const user = auth.user;
    if (user) {
      profile.value = {
        name: user.name || '',
        email: user.email || '',
        phone: user.phone || '',
        telegram_username: user.telegram_username || '',
        role: user.role || '',
      };
    }
  } catch (e) {
    errorMsg.value = e.response?.data?.message || t('crm.settings.loadError');
  } finally {
    loading.value = false;
  }
});

async function saveAgency() {
  saving.value = true;
  successMsg.value = '';
  errorMsg.value = '';
  try {
    const payload = Object.fromEntries(
      Object.entries(form.value).map(([k, v]) => [k, v === '' ? null : v])
    );
    await api.patch('/agency/settings', payload);
    successMsg.value = t('crm.settings.saved');
    setTimeout(() => { successMsg.value = ''; }, 3000);
  } catch (e) {
    errorMsg.value = e.response?.data?.message || t('crm.settings.saveError');
  } finally {
    saving.value = false;
  }
}

async function saveProfile() {
  savingProfile.value = true;
  profileSuccess.value = '';
  profileError.value = '';
  passwordError.value = '';
  passwordSuccess.value = '';

  try {
    const userId = auth.user?.id;
    if (userId) {
      await api.put(`/users/${userId}`, {
        phone: profile.value.phone || null,
        telegram_username: profile.value.telegram_username || null,
      });
    }

    if (passwords.new_password) {
      if (passwords.new_password.length < 8) {
        passwordError.value = t('crm.settings.min8chars');
        return;
      }
      if (passwords.new_password !== passwords.confirm) {
        passwordError.value = t('crm.settings.passwordsDontMatch');
        return;
      }
      try {
        await api.post(`/users/${userId}/password`, {
          password: passwords.new_password,
        });
        passwordSuccess.value = t('crm.settings.passwordChanged');
        passwords.current = '';
        passwords.new_password = '';
        passwords.confirm = '';
      } catch (e) {
        passwordError.value = e.response?.data?.message || t('crm.settings.passwordChangeError');
        return;
      }
    }

    profileSuccess.value = t('crm.settings.profileUpdated');
    setTimeout(() => { profileSuccess.value = ''; }, 3000);
  } catch (e) {
    profileError.value = e.response?.data?.message || t('crm.settings.profileSaveError');
  } finally {
    savingProfile.value = false;
  }
}
</script>
