<template>
  <div class="space-y-4">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Настройки</h1>
      <p class="text-sm text-gray-500 mt-1">Профиль агентства и личные данные</p>
    </div>

    <!-- Вкладки -->
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
      <button v-for="tab in tabs" :key="tab.key"
        @click="activeTab = tab.key"
        :class="['px-4 py-1.5 text-sm rounded-lg transition-all font-medium flex items-center gap-2',
          activeTab === tab.key
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-500 hover:text-gray-700']">
        <svg v-if="tab.key === 'agency'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
        </svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
        </svg>
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
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
                <h2 class="font-semibold text-gray-700 text-sm">Общая информация</h2>
              </div>
              <div class="p-5 space-y-4">
                <AppTextarea v-model="form.description" label="Описание агентства (RU)"
                  placeholder="Расскажите о вашем агентстве..." :maxlength="1000" :rows="3"
                  hint="Отображается в профиле на маркетплейсе" />
                <AppTextarea v-model="form.description_uz" label="Agentlik haqida (UZ)"
                  placeholder="Agentligingiz haqida aytib bering..." :maxlength="1000" :rows="3"
                  hint="O'zbek tilidagi tavsif" />

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
                        </svg>
                        Сайт
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
                        Город
                      </span>
                    </label>
                    <input v-model="form.city" type="text" placeholder="Ташкент"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Опыт (лет)
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
                <h2 class="font-semibold text-gray-700 text-sm">Контакты агентства</h2>
              </div>
              <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                        Телефон
                      </span>
                    </label>
                    <input v-model="form.phone" type="tel" placeholder="+998 90 123-45-67"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                        Email
                      </span>
                    </label>
                    <input v-model="form.email" type="email" placeholder="agency@example.com"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21"/>
                      </svg>
                      Адрес офиса
                    </span>
                  </label>
                  <input v-model="form.address" type="text" placeholder="Ул. Амира Тимура, 1, Ташкент"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Широта</label>
                    <input v-model="form.latitude" type="text" placeholder="41.2995"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors font-mono text-xs" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Долгота</label>
                    <input v-model="form.longitude" type="text" placeholder="69.2401"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors font-mono text-xs" />
                  </div>
                </div>
                <p class="text-xs text-gray-400 flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/>
                  </svg>
                  Координаты для отображения на карте в маркетплейсе
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
                <h2 class="font-semibold text-gray-700 text-sm">Команда</h2>
              </div>
              <div class="p-5 space-y-4">
                <!-- Видимость заявок -->
                <div class="p-3 rounded-lg border border-gray-100 bg-gray-50/50">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                      <p class="text-sm font-medium text-gray-700">Менеджеры видят все заявки</p>
                      <p class="text-xs text-gray-400 mt-0.5">Иначе каждый видит только свои</p>
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
                      Авто-распределение лидов
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
                <h2 class="font-semibold text-gray-700 text-sm">Направления</h2>
              </div>
              <div class="p-5">
                <p class="text-sm text-gray-500">
                  Управление направлениями в разделе
                  <router-link :to="{ name: 'countries' }" class="text-blue-600 font-medium hover:underline inline-flex items-center gap-1">
                    Страны
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
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
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
                  {{ profile.role === 'owner' ? 'Владелец' : profile.role === 'manager' ? 'Менеджер' : profile.role }}
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
                <h2 class="font-semibold text-gray-700 text-sm">Контактные данные</h2>
              </div>
              <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <AppInput v-model="profile.email" label="Email" type="email" disabled hint="Изменить email нельзя" />
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                        Телефон
                      </span>
                    </label>
                    <input v-model="profile.phone" type="tel" placeholder="+998 90 123-45-67"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors" />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-[#229ED9]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                      </svg>
                      Telegram
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
                <h2 class="font-semibold text-gray-700 text-sm">Смена пароля</h2>
              </div>
              <div class="p-5 space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Текущий пароль</label>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
                    <div class="relative">
                      <input v-model="passwords.new_password" :type="showNewPw ? 'text' : 'password'" placeholder="Минимум 8 символов"
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Повторите пароль</label>
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
                {{ savingProfile ? 'Сохранение...' : 'Сохранить профиль' }}
              </button>
            </div>
          </div>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import api from '@/api/index';
import AppInput from '@/components/AppInput.vue';
import AppTextarea from '@/components/AppTextarea.vue';
import { useAuthStore } from '@/stores/auth';

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

const tabs = [
  { key: 'agency',  label: 'Агентство' },
  { key: 'profile', label: 'Личный профиль' },
];

const leadModes = [
  { value: 'manual', label: 'Вручную', hint: 'Вы назначаете менеджера сами' },
  { value: 'round_robin', label: 'По очереди', hint: 'Round Robin между менеджерами' },
  { value: 'by_workload', label: 'По загрузке', hint: 'Менеджеру с меньшим числом дел' },
  { value: 'by_country', label: 'По стране', hint: 'Менеджеру с опытом по стране' },
];

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
  return ['', 'Слабый', 'Средний', 'Хороший', 'Надёжный'][passwordStrength.value] || '';
});

onMounted(async () => {
  try {
    const [settingsRes] = await Promise.all([
      api.get('/agency/settings'),
    ]);
    const data = settingsRes.data.data;
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
  } catch { /* ignore */ } finally {
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
    successMsg.value = 'Настройки агентства сохранены';
    setTimeout(() => { successMsg.value = ''; }, 3000);
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Ошибка при сохранении';
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
        passwordError.value = 'Минимум 8 символов';
        return;
      }
      if (passwords.new_password !== passwords.confirm) {
        passwordError.value = 'Пароли не совпадают';
        return;
      }
      try {
        await api.post(`/users/${userId}/password`, {
          password: passwords.new_password,
        });
        passwordSuccess.value = 'Пароль изменён';
        passwords.current = '';
        passwords.new_password = '';
        passwords.confirm = '';
      } catch (e) {
        passwordError.value = e.response?.data?.message || 'Ошибка смены пароля';
        return;
      }
    }

    profileSuccess.value = 'Профиль обновлён';
    setTimeout(() => { profileSuccess.value = ''; }, 3000);
  } catch (e) {
    profileError.value = e.response?.data?.message || 'Ошибка сохранения';
  } finally {
    savingProfile.value = false;
  }
}
</script>
