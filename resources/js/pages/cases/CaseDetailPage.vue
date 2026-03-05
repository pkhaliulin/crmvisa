<template>
  <!-- Loading -->
  <div v-if="loading" class="flex items-center justify-center py-32">
    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
  </div>

  <div v-else-if="caseData">

    <!-- ===== TOP BAR ===== -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <button @click="$router.back()"
          class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xl">{{ flagEmoji }}</span>
            <h1 class="text-lg font-bold text-gray-900">{{ countryName(caseData.country_code) }} -- {{ visaTypeName(caseData.visa_type) }}</h1>
            <AppBadge :color="priorityColor">{{ priorityLabel }}</AppBadge>
          </div>
          <p class="text-xs text-gray-400 font-mono mt-0.5">{{ caseData.case_number }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button @click="showMoveModal = true" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 font-medium transition-colors">Сменить этап</button>
        <button @click="confirmDelete" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-400 hover:bg-red-50 font-medium transition-colors">Удалить</button>
      </div>
    </div>

    <!-- ===== STAGE STEPPER ===== -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 mb-4 overflow-x-auto scrollbar-hide">
      <div class="flex items-center min-w-[560px]">
        <template v-for="(st, idx) in STAGES" :key="st.key">
          <!-- Connector line -->
          <div v-if="idx > 0" class="flex-1 h-0.5 mx-0.5 rounded-full transition-colors duration-500" :class="stageIdx >= idx ? 'bg-blue-500' : 'bg-gray-200'"></div>
          <!-- Step circle + label -->
          <button class="flex flex-col items-center gap-1 shrink-0 group" @click="stageIdx !== idx && (moveForm.stage = st.key, showMoveModal = true)">
            <div :class="['w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 group-hover:scale-110', stepClass(idx)]">
              <svg v-if="stageIdx > idx" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
              <span v-else>{{ idx + 1 }}</span>
            </div>
            <span :class="['text-[10px] font-medium whitespace-nowrap transition-colors', stageIdx === idx ? 'text-blue-700 font-bold' : stageIdx > idx ? 'text-gray-400' : 'text-gray-300 group-hover:text-gray-400']">{{ st.label }}</span>
          </button>
        </template>
      </div>
    </div>

    <!-- ===== MOBILE CLIENT STRIP (lg:hidden) ===== -->
    <div class="lg:hidden bg-white rounded-xl border border-gray-100 p-3 mb-4 flex items-center justify-between">
      <div class="min-w-0">
        <p class="text-sm font-bold text-gray-900 truncate">{{ caseData.client?.name ?? '---' }}</p>
        <p v-if="caseData.client?.phone" class="text-xs text-gray-400">{{ caseData.client.phone }}</p>
      </div>
      <div v-if="caseData.client?.phone" class="flex gap-2 shrink-0 ml-3">
        <a :href="'tel:' + caseData.client.phone" class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 font-medium">Звонок</a>
        <a :href="'https://wa.me/' + cleanPhone(caseData.client.phone)" target="_blank" class="text-xs px-3 py-1.5 rounded-lg bg-green-50 text-green-600 font-medium">WA</a>
      </div>
    </div>

    <!-- ===== 2-COLUMN LAYOUT ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

      <!-- LEFT: Main work area (8/12) -->
      <div class="lg:col-span-8 space-y-4">

        <!-- ===== ACTION CARD ===== -->
        <div :class="['rounded-xl border-2 p-5 transition-all duration-300', actionCardClass]">

          <!-- === OWNER: Assign manager block (if no assignee) === -->
          <div v-if="!caseData.assignee && isOwner" class="mb-4">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></div>
              <span class="text-[10px] font-bold uppercase tracking-widest text-orange-500">Требуется действие руководителя</span>
            </div>
            <h2 class="text-base font-bold text-gray-900 mb-3">Назначьте менеджера на заявку</h2>
            <p class="text-sm text-gray-500 mb-4">Пока менеджер не назначен, работа по заявке не может начаться. Выберите ответственного менеджера из списка.</p>

            <div class="flex items-end gap-3">
              <div class="flex-1 max-w-xs">
                <label class="text-xs text-gray-500 font-medium mb-1 block">Менеджер</label>
                <select v-model="assignForm.manager_id"
                  class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                  <option value="">Выберите менеджера...</option>
                  <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
              </div>
              <button @click="doAssign" :disabled="!assignForm.manager_id || assignForm.loading"
                class="px-5 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white text-sm font-semibold shadow-md shadow-blue-200 hover:shadow-lg active:scale-[0.98] transition-all duration-200 disabled:opacity-50">
                {{ assignForm.loading ? 'Назначение...' : 'Назначить' }}
              </button>
            </div>

            <!-- SLA/deadline info for owner -->
            <div v-if="caseData.critical_date || slaInfo" class="mt-4 pt-3 border-t border-orange-100 flex items-center gap-4 flex-wrap">
              <div v-if="slaInfo" class="flex items-center gap-2">
                <span :class="['text-xs font-bold', slaInfo.value]">SLA: {{ slaInfo.display }}</span>
                <span :class="['text-xs', slaInfo.sub]">{{ slaInfo.subText }}</span>
              </div>
              <div v-if="caseData.critical_date" class="flex items-center gap-2">
                <span class="text-xs text-gray-400">Дедлайн:</span>
                <span :class="['text-xs font-bold', deadlineClass]">{{ fmtShort(caseData.critical_date) }}</span>
              </div>
              <div v-if="caseData.days_left != null" class="flex items-center gap-2">
                <span :class="['text-xs font-bold', caseData.days_left < 0 ? 'text-red-600' : caseData.days_left <= 7 ? 'text-yellow-600' : 'text-green-600']">
                  {{ caseData.days_left < 0 ? 'Просрочено на ' + Math.abs(caseData.days_left) + ' дн.' : caseData.days_left + ' дн. осталось' }}
                </span>
              </div>
            </div>
          </div>

          <!-- === NORMAL: Stage tasks (when manager assigned OR user is manager) === -->
          <template v-if="caseData.assignee || !isOwner">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                  <div :class="['w-2 h-2 rounded-full animate-pulse', actionDotClass]"></div>
                  <span :class="['text-[10px] font-bold uppercase tracking-widest', actionLabelClass]">
                    {{ STAGE_LABELS[caseData.stage] }}
                  </span>
                </div>
                <h2 class="text-base font-bold text-gray-900 leading-snug">{{ currentStageConfig?.manager_goal }}</h2>
              </div>

              <!-- SLA Timer -->
              <div v-if="slaInfo" class="shrink-0">
                <div :class="['rounded-xl px-4 py-3 text-center min-w-[80px]', slaInfo.bg]">
                  <p :class="['text-[9px] uppercase tracking-widest font-bold', slaInfo.label]">SLA</p>
                  <p :class="['text-2xl font-black tabular-nums leading-tight', slaInfo.value]">{{ slaInfo.display }}</p>
                  <p :class="['text-[10px]', slaInfo.sub]">{{ slaInfo.subText }}</p>
                </div>
              </div>
            </div>

            <!-- Tasks -->
            <div class="mt-3 space-y-1.5">
              <div v-for="(task, ti) in currentStageConfig?.manager_tasks ?? []" :key="ti"
                class="flex items-start gap-2.5 group">
                <div class="w-4 h-4 rounded border border-gray-200 flex items-center justify-center shrink-0 mt-0.5 group-hover:border-blue-300 transition-colors">
                  <span class="text-[9px] text-gray-300 group-hover:text-blue-400">{{ ti + 1 }}</span>
                </div>
                <span class="text-sm text-gray-600 leading-snug">{{ task }}</span>
              </div>
            </div>

            <!-- Result hint -->
            <div v-if="currentStageConfig?.manager_result" class="mt-3 pt-3 border-t border-dashed" :class="actionBorderClass">
              <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                <p class="text-xs text-gray-400">{{ currentStageConfig.manager_result }}</p>
              </div>
            </div>
          </template>

          <!-- ===== STAGE-SPECIFIC CTA ===== -->
          <div v-if="caseData.assignee || !isOwner" class="mt-4 pt-4 border-t" :class="actionBorderClass">

            <!-- lead -->
            <button v-if="caseData.stage === 'lead'" @click="quickMove('qualification')"
              class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white text-sm font-semibold shadow-md shadow-blue-200 hover:shadow-lg hover:shadow-blue-300 active:scale-[0.98] transition-all duration-200">
              Клиент на связи -- перейти к квалификации
            </button>

            <!-- qualification -->
            <button v-if="caseData.stage === 'qualification'" @click="quickMove('documents')"
              class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white text-sm font-semibold shadow-md shadow-blue-200 hover:shadow-lg hover:shadow-blue-300 active:scale-[0.98] transition-all duration-200">
              Чек-лист готов -- начать сбор документов
            </button>

            <!-- documents -->
            <div v-if="caseData.stage === 'documents'">
              <div class="flex items-center gap-3 mb-2">
                <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-700 ease-out"
                    :class="docProgress >= 100 ? 'bg-green-500' : 'bg-blue-500'"
                    :style="{ width: docProgress + '%' }"></div>
                </div>
                <span :class="['text-xs font-bold tabular-nums', docProgress >= 100 ? 'text-green-600' : 'text-blue-600']">{{ docProgress }}%</span>
              </div>
              <button v-if="docProgress >= 100" @click="quickMove('doc_review')"
                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-green-600 to-green-500 text-white text-sm font-semibold shadow-md shadow-green-200 hover:shadow-lg hover:shadow-green-300 active:scale-[0.98] transition-all duration-200">
                Все загружено -- начать проверку
              </button>
              <p v-else class="text-xs text-gray-400">Ожидание загрузки документов от клиента</p>
            </div>

            <!-- doc_review -->
            <div v-if="caseData.stage === 'doc_review'">
              <div class="flex items-center gap-4 text-xs text-gray-500 mb-2">
                <span>Проверено: <strong class="text-gray-800">{{ reviewedCount }}</strong> / {{ totalDocsCount }}</span>
                <span v-if="needsTranslationCount > 0" class="text-purple-600 font-medium">На перевод: {{ needsTranslationCount }}</span>
              </div>
              <div class="flex gap-2">
                <button v-if="needsTranslationCount > 0 && allReviewed" @click="quickMove('translation')"
                  class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-purple-500 text-white text-sm font-semibold shadow-md shadow-purple-200 hover:shadow-lg active:scale-[0.98] transition-all duration-200">
                  Переводы нужны -- далее
                </button>
                <button v-if="needsTranslationCount === 0 && allReviewed" @click="quickMove('ready')"
                  class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-green-600 to-green-500 text-white text-sm font-semibold shadow-md shadow-green-200 hover:shadow-lg active:scale-[0.98] transition-all duration-200">
                  Все OK -- к подаче
                </button>
              </div>
            </div>

            <!-- translation -->
            <div v-if="caseData.stage === 'translation'">
              <div class="flex items-center gap-3 text-xs text-gray-500 mb-2">
                <span>Переведено: <strong class="text-gray-800">{{ translatedCount }}</strong> / {{ needsTranslationCount }}</span>
              </div>
              <button v-if="allTranslated" @click="quickMove('ready')"
                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-green-600 to-green-500 text-white text-sm font-semibold shadow-md shadow-green-200 hover:shadow-lg active:scale-[0.98] transition-all duration-200">
                Все переводы готовы -- к подаче
              </button>
            </div>

            <!-- ready -->
            <div v-if="caseData.stage === 'ready'">
              <div class="grid grid-cols-2 gap-3 mb-3">
                <AppInput v-model="embassyForm.submitted_at" type="date" label="Дата подачи" />
                <AppInput v-model="embassyForm.expected_result_date" type="date" label="Ожидаемый результат" />
              </div>
              <AppButton :loading="embassyForm.loading" size="sm" @click="doSubmitToEmbassy">Отметить подачу</AppButton>
            </div>

            <!-- review -->
            <div v-if="caseData.stage === 'review'">
              <div class="flex items-center gap-4 text-xs text-gray-500 mb-3 flex-wrap">
                <span v-if="caseData.submitted_at">Подано: <strong>{{ fmtShort(caseData.submitted_at) }}</strong></span>
                <span v-if="caseData.expected_result_date">Ожидание: <strong>{{ fmtShort(caseData.expected_result_date) }}</strong></span>
                <span v-if="daysUntilResult !== null" :class="daysUntilResult < 0 ? 'text-red-600 font-bold' : ''">
                  {{ daysUntilResult < 0 ? `Просрочено ${Math.abs(daysUntilResult)} дн.` : `${daysUntilResult} дн. до результата` }}
                </span>
              </div>
              <div class="flex gap-2">
                <AppButton size="sm" @click="showResultModal = true">Записать результат</AppButton>
                <AppButton variant="outline" size="sm" @click="showExpectedDateModal = true">Изменить дату</AppButton>
              </div>
            </div>

            <!-- result -->
            <div v-if="caseData.stage === 'result'" :class="['rounded-lg p-4', caseData.result_type === 'approved' ? 'bg-green-50' : 'bg-red-50']">
              <p :class="['font-bold text-sm mb-1', caseData.result_type === 'approved' ? 'text-green-800' : 'text-red-800']">
                {{ caseData.result_type === 'approved' ? 'Виза одобрена' : 'Виза отклонена' }}
              </p>
              <div :class="['text-xs space-y-0.5', caseData.result_type === 'approved' ? 'text-green-700' : 'text-red-700']">
                <p v-if="caseData.result_notes">{{ caseData.result_notes }}</p>
                <p v-if="caseData.visa_issued_at">Выдана: {{ fmtShort(caseData.visa_issued_at) }}</p>
                <p v-if="caseData.visa_received_at">Получена: {{ fmtShort(caseData.visa_received_at) }}</p>
                <p v-if="caseData.visa_validity">Срок: {{ caseData.visa_validity }}</p>
                <p v-if="caseData.rejection_reason">Причина: {{ caseData.rejection_reason }}</p>
                <p v-if="caseData.can_reapply !== null && caseData.can_reapply !== undefined">Повторная подача: {{ caseData.can_reapply ? 'Да' : 'Нет' }}</p>
                <p v-if="caseData.reapply_recommendation">Рекомендация: {{ caseData.reapply_recommendation }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== DOCUMENTS ===== -->
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
          <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50">
            <div class="flex items-center gap-3">
              <h3 class="font-semibold text-gray-800 text-sm">Документы</h3>
              <span v-if="checklist.progress" class="text-xs text-gray-400 tabular-nums">
                {{ checklist.progress.uploaded }}/{{ checklist.progress.total }} загружено
              </span>
            </div>
            <div class="flex items-center gap-2">
              <button v-if="uploadedCount > 0" @click="downloadZip" :disabled="zipLoading"
                class="text-xs text-gray-500 hover:text-gray-700 px-2.5 py-1 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                {{ zipLoading ? 'ZIP...' : 'Скачать ZIP' }}
              </button>
              <button @click="showAddSlot = true"
                class="text-xs text-blue-600 hover:text-blue-700 px-2.5 py-1 rounded-lg hover:bg-blue-50 font-medium transition-colors">
                + Документ
              </button>
            </div>
          </div>

          <!-- Progress -->
          <div v-if="checklist.progress?.total > 0" class="px-5 pt-3">
            <div class="w-full bg-gray-100 rounded-full h-1 overflow-hidden">
              <div class="h-full rounded-full transition-all duration-700 ease-out"
                :class="checklist.progress.percent === 100 ? 'bg-green-500' : 'bg-blue-500'"
                :style="{ width: checklist.progress.percent + '%' }"></div>
            </div>
          </div>

          <!-- Action-needed -->
          <div v-if="actionDocs.length" class="px-5 pt-4">
            <p class="text-[10px] uppercase tracking-widest font-bold text-orange-500 mb-2">Требуют внимания ({{ actionDocs.length }})</p>
            <div class="space-y-2">
              <DocItem v-for="item in actionDocs" :key="item.id" :item="item"
                @upload="uploadToSlot" @toggle="toggleCheck" @review="reviewSlot"
                @reject="openReject" @translation="openTranslation"
                @upload-translation="doUploadTranslation" @approve-translation="doApproveTranslation"
                @preview="openPreview" @delete="deleteSlot" @repeat="repeatSlot" />
            </div>
          </div>

          <!-- Done docs -->
          <div v-if="otherDocs.length" class="px-5 pt-4">
            <p v-if="actionDocs.length" class="text-[10px] uppercase tracking-widest font-bold text-gray-300 mb-2">Готовые ({{ otherDocs.length }})</p>
            <div class="space-y-2">
              <DocItem v-for="item in otherDocs" :key="item.id" :item="item"
                @upload="uploadToSlot" @toggle="toggleCheck" @review="reviewSlot"
                @reject="openReject" @translation="openTranslation"
                @upload-translation="doUploadTranslation" @approve-translation="doApproveTranslation"
                @preview="openPreview" @delete="deleteSlot" @repeat="repeatSlot" />
            </div>
          </div>

          <p v-if="!checklist.items?.length" class="text-sm text-gray-400 py-8 text-center">Чек-лист пуст</p>
          <div class="h-4"></div>
        </div>

        <!-- ===== TIMELINE (collapsed) ===== -->
        <div class="bg-white rounded-xl border border-gray-100">
          <button @click="timelineOpen = !timelineOpen"
            class="flex items-center justify-between w-full px-5 py-3 text-left hover:bg-gray-50/50 transition-colors rounded-xl">
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-gray-800 text-sm">История</h3>
              <span class="text-xs text-gray-300">{{ caseData.stage_history?.length ?? 0 }} записей</span>
            </div>
            <svg :class="['w-4 h-4 text-gray-400 transition-transform duration-200', timelineOpen ? 'rotate-180' : '']"
              fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div v-if="timelineOpen" class="px-5 pb-4 space-y-3">
            <div v-for="h in caseData.stage_history" :key="h.id" class="flex items-start gap-3">
              <div class="w-1.5 h-1.5 rounded-full bg-blue-400 mt-2 shrink-0"></div>
              <div>
                <p class="text-sm font-medium text-gray-700">{{ STAGE_LABELS[h.stage] ?? h.stage }}</p>
                <p class="text-xs text-gray-400">{{ fmtFull(h.entered_at) }} · {{ h.user?.name ?? 'Система' }}</p>
                <p v-if="h.notes" class="text-xs text-gray-500 mt-0.5">{{ h.notes }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT: Sidebar (4/12) -->
      <div class="lg:col-span-4 space-y-4">

        <!-- Client -->
        <div class="bg-white rounded-xl border border-gray-100 p-4">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Клиент</p>
          <p class="text-sm font-bold text-gray-900">{{ caseData.client?.name ?? '---' }}</p>
          <p v-if="caseData.client?.phone" class="text-xs text-gray-500 mt-0.5">{{ caseData.client.phone }}</p>
          <p v-if="caseData.client?.email" class="text-xs text-gray-500">{{ caseData.client.email }}</p>
          <div v-if="caseData.client?.phone" class="mt-3 flex gap-2">
            <a :href="'tel:' + caseData.client.phone"
              class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 font-medium transition-colors">Позвонить</a>
            <a :href="'https://wa.me/' + cleanPhone(caseData.client.phone)" target="_blank"
              class="text-xs px-3 py-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 font-medium transition-colors">WhatsApp</a>
          </div>
        </div>

        <!-- Case meta -->
        <div class="bg-white rounded-xl border border-gray-100 p-4">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Информация</p>
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-400">Этап</span>
              <AppBadge :color="stageColor">{{ stageLabel }}</AppBadge>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-400">Приоритет</span>
              <AppBadge :color="priorityColor">{{ priorityLabel }}</AppBadge>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-400">Дедлайн</span>
              <span :class="['text-xs font-semibold', deadlineClass]">{{ caseData.critical_date ? fmtShort(caseData.critical_date) : '---' }}</span>
            </div>
            <div v-if="caseData.days_left != null" class="flex items-center justify-between">
              <span class="text-xs text-gray-400">Дней осталось</span>
              <span :class="['text-xs font-bold', caseData.days_left < 0 ? 'text-red-600' : caseData.days_left <= 7 ? 'text-yellow-600' : 'text-gray-700']">{{ caseData.days_left }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-400">Менеджер</span>
              <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-700 font-medium">{{ caseData.assignee?.name ?? '---' }}</span>
                <button v-if="isOwner" @click="showAssignModal = true"
                  class="text-[10px] text-blue-500 hover:text-blue-700 font-medium">
                  {{ caseData.assignee ? 'изм.' : 'назначить' }}
                </button>
              </div>
            </div>
            <div v-if="caseData.appointment_date" class="flex items-center justify-between">
              <span class="text-xs text-gray-400">Прием</span>
              <span class="text-xs text-green-600 font-medium">{{ caseData.appointment_date }}{{ caseData.appointment_time ? ' ' + caseData.appointment_time : '' }}</span>
            </div>
            <div v-if="caseData.submitted_at" class="flex items-center justify-between">
              <span class="text-xs text-gray-400">Подано</span>
              <span class="text-xs text-gray-700">{{ fmtShort(caseData.submitted_at) }}</span>
            </div>
            <div v-if="caseData.expected_result_date" class="flex items-center justify-between">
              <span class="text-xs text-gray-400">Ожидание</span>
              <span class="text-xs text-gray-700">{{ fmtShort(caseData.expected_result_date) }}</span>
            </div>
          </div>
        </div>

        <!-- Payment -->
        <div v-if="caseData.payment_status" class="bg-white rounded-xl border border-gray-100 p-4">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Оплата</p>
          <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', paymentBadgeClass]">{{ paymentLabel }}</span>
          <p v-if="caseData.total_amount" class="text-lg font-black text-gray-900 mt-2">
            {{ Number(caseData.total_amount).toLocaleString() }} <span class="text-xs font-normal text-gray-400">сум</span>
          </p>
        </div>

        <!-- Quick actions -->
        <div class="bg-white rounded-xl border border-gray-100 p-4">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Действия</p>
          <div class="space-y-1">
            <button @click="showMoveModal = true" class="w-full text-left text-xs px-3 py-2 rounded-lg hover:bg-gray-50 text-gray-600 transition-colors">Сменить этап</button>
            <button @click="showAddSlot = true" class="w-full text-left text-xs px-3 py-2 rounded-lg hover:bg-gray-50 text-gray-600 transition-colors">Добавить документ</button>
            <button v-if="uploadedCount > 0" @click="downloadZip" class="w-full text-left text-xs px-3 py-2 rounded-lg hover:bg-gray-50 text-gray-600 transition-colors">Скачать все (ZIP)</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== MODALS ===== -->
  <AppModal v-model="showMoveModal" title="Сменить этап">
    <div class="space-y-4">
      <AppSelect v-model="moveForm.stage" :options="stageOptions" label="Новый этап" />
      <AppInput v-model="moveForm.notes" label="Комментарий" placeholder="Необязательно..." />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showMoveModal = false">Отмена</AppButton>
        <AppButton :loading="moveForm.loading" @click="doMoveStage">Переместить</AppButton>
      </div>
    </div>
  </AppModal>

  <AppModal v-model="showAddSlot" title="Добавить документ">
    <div class="space-y-4">
      <AppInput v-model="newSlot.name" label="Название" placeholder="Справка из налоговой" />
      <AppInput v-model="newSlot.description" label="Пояснение" placeholder="Что именно нужно..." />
      <div class="flex items-center gap-2">
        <input type="checkbox" v-model="newSlot.is_required" id="slotReq" class="rounded" />
        <label for="slotReq" class="text-sm text-gray-700">Обязательный</label>
      </div>
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showAddSlot = false">Отмена</AppButton>
        <AppButton :loading="newSlot.loading" @click="addSlot">Добавить</AppButton>
      </div>
    </div>
  </AppModal>

  <AppModal v-model="showRejectModal" title="Причина отклонения">
    <div class="space-y-4">
      <AppInput v-model="rejectNote" label="Комментарий" placeholder="Что не так..." />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showRejectModal = false">Отмена</AppButton>
        <AppButton variant="danger" @click="submitReject">Отклонить</AppButton>
      </div>
    </div>
  </AppModal>

  <AppModal v-model="showTranslationModal" title="Отправить на перевод">
    <div class="space-y-4">
      <p class="text-sm text-gray-600">Документ: <strong>{{ translationItem?.name }}</strong></p>
      <AppInput v-model="translationForm.pages" type="number" label="Страниц" placeholder="1" />
      <AppInput v-model="translationForm.notes" label="Комментарий" placeholder="Что перевести..." />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showTranslationModal = false">Отмена</AppButton>
        <AppButton @click="submitTranslation">На перевод</AppButton>
      </div>
    </div>
  </AppModal>

  <AppModal v-model="showResultModal" title="Записать результат">
    <div class="space-y-4">
      <div class="flex gap-3">
        <button @click="resultForm.result_type = 'approved'"
          :class="['flex-1 py-3 rounded-xl border-2 text-center font-medium text-sm transition-colors', resultForm.result_type === 'approved' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 text-gray-400 hover:border-gray-300']">Одобрена</button>
        <button @click="resultForm.result_type = 'rejected'"
          :class="['flex-1 py-3 rounded-xl border-2 text-center font-medium text-sm transition-colors', resultForm.result_type === 'rejected' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 text-gray-400 hover:border-gray-300']">Отказ</button>
      </div>
      <template v-if="resultForm.result_type === 'approved'">
        <AppInput v-model="resultForm.visa_issued_at" type="date" label="Дата выдачи" />
        <AppInput v-model="resultForm.visa_received_at" type="date" label="Дата получения" />
        <AppInput v-model="resultForm.visa_validity" label="Срок действия" placeholder="90 дней / 1 год..." />
      </template>
      <template v-if="resultForm.result_type === 'rejected'">
        <AppInput v-model="resultForm.rejection_reason" label="Причина отказа" />
        <div class="flex items-center gap-2">
          <input type="checkbox" v-model="resultForm.can_reapply" id="canReapply" class="rounded" />
          <label for="canReapply" class="text-sm text-gray-700">Повторная подача возможна</label>
        </div>
        <AppInput v-if="resultForm.can_reapply" v-model="resultForm.reapply_recommendation" label="Рекомендация" />
      </template>
      <AppInput v-model="resultForm.result_notes" label="Примечание" />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showResultModal = false">Отмена</AppButton>
        <AppButton :loading="resultForm.loading" @click="doComplete" :disabled="!resultForm.result_type">Сохранить</AppButton>
      </div>
    </div>
  </AppModal>

  <AppModal v-model="showExpectedDateModal" title="Изменить дату">
    <div class="space-y-4">
      <AppInput v-model="expectedDateForm.expected_result_date" type="date" label="Новая дата" />
      <AppInput v-model="expectedDateForm.notes" label="Причина" />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showExpectedDateModal = false">Отмена</AppButton>
        <AppButton :loading="expectedDateForm.loading" @click="doUpdateExpectedDate">Сохранить</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- Assign manager modal -->
  <AppModal v-model="showAssignModal" title="Назначить менеджера">
    <div class="space-y-4">
      <div>
        <label class="text-sm text-gray-600 font-medium mb-1 block">Менеджер</label>
        <select v-model="assignForm.manager_id"
          class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
          <option value="">Выберите менеджера...</option>
          <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }} ({{ m.email }})</option>
        </select>
      </div>
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showAssignModal = false">Отмена</AppButton>
        <AppButton :loading="assignForm.loading" :disabled="!assignForm.manager_id" @click="doAssignFromModal">Назначить</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- File preview -->
  <div v-if="preview" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4" @click.self="preview = null">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
      <div class="flex items-center justify-between px-5 py-4 border-b shrink-0">
        <p class="font-medium text-gray-800 truncate max-w-[70%]">{{ preview.original_name }}</p>
        <div class="flex items-center gap-4">
          <a :href="preview.url" download class="text-sm text-blue-600 hover:underline">Скачать</a>
          <button @click="preview = null" class="text-gray-400 hover:text-gray-700 text-xl leading-none">x</button>
        </div>
      </div>
      <div class="flex-1 overflow-auto p-4 min-h-0">
        <img v-if="isImage(preview.mime_type)" :src="preview.url" class="max-w-full mx-auto rounded-lg shadow" />
        <iframe v-else-if="isPdf(preview.mime_type)" :src="preview.url" class="w-full rounded-lg border" style="height:70vh"></iframe>
        <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
          <p class="text-sm">Предпросмотр недоступен</p>
          <a :href="preview.url" download class="mt-4 text-blue-600 text-sm hover:underline">Скачать файл</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { casesApi } from '@/api/cases';
import { useCountries } from '@/composables/useCountries';
import AppBadge  from '@/components/AppBadge.vue';
import AppButton from '@/components/AppButton.vue';
import AppModal  from '@/components/AppModal.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppInput  from '@/components/AppInput.vue';
import DocItem   from '@/components/DocItem.vue';
import { useAuthStore } from '@/stores/auth';
import { usersApi } from '@/api/users';

const { countryName, countryFlag, visaTypeName } = useCountries();
const auth = useAuthStore();
const isOwner = computed(() => auth.isOwner);
const route  = useRoute();
const router = useRouter();
const id     = route.params.id;

// State
const caseData  = ref(null);
const checklist = ref({ items: [], progress: null });
const loading   = ref(true);
const timelineOpen = ref(false);
const showMoveModal = ref(false);
const showAddSlot = ref(false);
const showRejectModal = ref(false);
const showTranslationModal = ref(false);
const showResultModal = ref(false);
const showExpectedDateModal = ref(false);
const rejectNote = ref('');
const rejectItem = ref(null);
const translationItem = ref(null);
const preview = ref(null);
const zipLoading = ref(false);

const managers = ref([]);
const showAssignModal = ref(false);
const assignForm = reactive({ manager_id: '', loading: false });
const moveForm = reactive({ stage: '', notes: '', loading: false });
const newSlot = reactive({ name: '', description: '', is_required: false, loading: false });
const translationForm = reactive({ pages: 1, notes: '' });
const embassyForm = reactive({ submitted_at: '', expected_result_date: '', loading: false });
const resultForm = reactive({
  result_type: '', result_notes: '',
  visa_issued_at: '', visa_received_at: '', visa_validity: '',
  rejection_reason: '', can_reapply: false, reapply_recommendation: '', loading: false,
});
const expectedDateForm = reactive({ expected_result_date: '', notes: '', loading: false });

// Constants
const STAGES = [
  { key: 'lead', label: 'Лид' },
  { key: 'qualification', label: 'Квалификация' },
  { key: 'documents', label: 'Документы' },
  { key: 'doc_review', label: 'Проверка' },
  { key: 'translation', label: 'Перевод' },
  { key: 'ready', label: 'Подача' },
  { key: 'review', label: 'Рассмотрение' },
  { key: 'result', label: 'Результат' },
];
const STAGE_LABELS = Object.fromEntries(STAGES.map(s => [s.key, s.label]));
const STAGE_COLORS = {
  lead: 'gray', qualification: 'blue', documents: 'purple',
  doc_review: 'orange', translation: 'yellow', ready: 'blue', review: 'blue', result: 'green',
};
const stageOptions = STAGES.map(s => ({ value: s.key, label: s.label }));

const STAGE_CONFIG = {
  lead: { manager_goal: 'Связаться с клиентом в течение 1 часа', manager_tasks: ['Позвонить или написать клиенту', 'Уточнить цель поездки и сроки', 'Выяснить состав группы', 'Подтвердить контактные данные'], manager_result: 'Клиент на связи, потребность понятна' },
  qualification: { manager_goal: 'Оценить заявку и подготовить предложение за 3 часа', manager_tasks: ['Проверить срок действия паспорта (мин. 6 мес.)', 'Определить тип визы и категорию', 'Сформировать чек-лист документов', 'Рассчитать стоимость и сроки', 'Озвучить клиенту условия'], manager_result: 'Чек-лист готов, стоимость озвучена, клиент согласен' },
  documents: { manager_goal: 'Собрать все документы за 72 часа', manager_tasks: ['Отправить клиенту чек-лист с пояснениями', 'Контролировать загрузку', 'Проверять качество сканов', 'Запрашивать недостающие документы'], manager_result: 'Все документы загружены и готовы к проверке' },
  doc_review: { manager_goal: 'Проверить документы за 24 часа', manager_tasks: ['Проверить соответствие требованиям посольства', 'Проверить актуальность справок', 'Определить документы для перевода', 'Отклонить некачественные сканы'], manager_result: 'Документы проверены, список на перевод готов' },
  translation: { manager_goal: 'Получить переводы за 48 часов', manager_tasks: ['Отправить документы переводчику', 'Контролировать сроки', 'Проверить качество перевода', 'Заверить нотариально'], manager_result: 'Все переводы готовы, пакет полный' },
  ready: { manager_goal: 'Подготовить к подаче за 24 часа', manager_tasks: ['Проверить полноту пакета', 'Забронировать слот в посольстве', 'Подтвердить дату с клиентом', 'Проинструктировать клиента'], manager_result: 'Пакет готов, запись подтверждена' },
  review: { manager_goal: 'Отслеживать статус и информировать клиента', manager_tasks: ['Мониторить статус на сайте посольства', 'Информировать клиента', 'Подготовить доп. документы при запросе', 'Отвечать на вопросы о сроках'], manager_result: 'Решение получено от посольства' },
  result: { manager_goal: 'Завершить заявку за 4 часа', manager_tasks: ['Сообщить клиенту результат', 'Одобрено: организовать выдачу паспорта', 'Отказ: разъяснить причины', 'Отказ: подготовить план повторной подачи'], manager_result: 'Заявка закрыта или план повторной подачи готов' },
};

// Computed
const flagEmoji = computed(() => countryFlag(caseData.value?.country_code ?? ''));
const stageLabel = computed(() => STAGE_LABELS[caseData.value?.stage] ?? '');
const stageColor = computed(() => STAGE_COLORS[caseData.value?.stage] ?? 'gray');
const stageIdx = computed(() => STAGES.findIndex(s => s.key === caseData.value?.stage));
const currentStageConfig = computed(() => STAGE_CONFIG[caseData.value?.stage]);

const priorityMap = { low: 'Низкий', normal: 'Обычный', high: 'Высокий', urgent: 'Срочный' };
const priorityColorMap = { low: 'gray', normal: 'blue', high: 'orange', urgent: 'red' };
const priorityLabel = computed(() => priorityMap[caseData.value?.priority] ?? '');
const priorityColor = computed(() => priorityColorMap[caseData.value?.priority] ?? 'gray');
const deadlineClass = computed(() => {
  const u = caseData.value?.urgency;
  return u === 'overdue' ? 'text-red-600' : u === 'critical' ? 'text-yellow-600' : 'text-gray-700';
});

const uploadedCount = computed(() => (checklist.value.items ?? []).filter(i => i.document || i.is_checked).length);
const totalDocsCount = computed(() => (checklist.value.items ?? []).length);
const reviewedCount = computed(() => (checklist.value.items ?? []).filter(i => ['approved','rejected','needs_translation','translated','translation_approved'].includes(i.status)).length);
const needsTranslationCount = computed(() => (checklist.value.items ?? []).filter(i => ['needs_translation','translated','translation_approved'].includes(i.status) || i.review_status === 'needs_translation').length);
const translatedCount = computed(() => (checklist.value.items ?? []).filter(i => ['translated','translation_approved'].includes(i.status)).length);
const allReviewed = computed(() => reviewedCount.value === totalDocsCount.value && totalDocsCount.value > 0);
const allTranslated = computed(() => translatedCount.value >= needsTranslationCount.value && needsTranslationCount.value > 0);
const docProgress = computed(() => checklist.value.progress?.percent ?? 0);

const actionDocs = computed(() => (checklist.value.items ?? []).filter(i => {
  if (i.status === 'uploaded') return true;
  if (i.status === 'rejected') return true;
  if (i.status === 'needs_translation') return true;
  if (i.status === 'translated') return true;
  if (!i.document && !i.is_checked && i.type !== 'checkbox') return true;
  return false;
}));
const otherDocs = computed(() => {
  const ids = new Set(actionDocs.value.map(d => d.id));
  return (checklist.value.items ?? []).filter(i => !ids.has(i.id));
});

// SLA
const slaInfo = computed(() => {
  const h = caseData.value?.stage_sla_hours_left;
  if (h == null) return null;
  if (caseData.value?.stage_sla_overdue || h < 0) return { display: Math.abs(h) + 'ч', subText: 'просрочено', bg: 'bg-red-50', label: 'text-red-400', value: 'text-red-600', sub: 'text-red-400' };
  if (h <= 2) return { display: h + 'ч', subText: 'осталось', bg: 'bg-orange-50', label: 'text-orange-400', value: 'text-orange-600', sub: 'text-orange-400' };
  return { display: h + 'ч', subText: 'осталось', bg: 'bg-blue-50', label: 'text-blue-400', value: 'text-blue-600', sub: 'text-blue-400' };
});

const daysUntilResult = computed(() => {
  if (!caseData.value?.expected_result_date) return null;
  return Math.ceil((new Date(caseData.value.expected_result_date) - new Date()) / 86400000);
});

// Action card styling
const actionCardClass = computed(() => {
  if (caseData.value?.stage === 'result') return caseData.value.result_type === 'approved' ? 'border-green-200 bg-green-50/30' : 'border-red-200 bg-red-50/30';
  if (caseData.value?.stage_sla_overdue) return 'border-red-300 bg-red-50/50';
  if (slaInfo.value && caseData.value?.stage_sla_hours_left <= 2) return 'border-orange-200 bg-orange-50/30';
  return 'border-blue-200 bg-gradient-to-br from-blue-50/40 to-white';
});
const actionDotClass = computed(() => {
  if (caseData.value?.stage_sla_overdue) return 'bg-red-500';
  if (slaInfo.value && caseData.value?.stage_sla_hours_left <= 2) return 'bg-orange-500';
  return 'bg-blue-500';
});
const actionLabelClass = computed(() => {
  if (caseData.value?.stage_sla_overdue) return 'text-red-500';
  if (slaInfo.value && caseData.value?.stage_sla_hours_left <= 2) return 'text-orange-500';
  return 'text-blue-500';
});
const actionBorderClass = computed(() => caseData.value?.stage_sla_overdue ? 'border-red-100' : 'border-gray-100');

const paymentBadgeClass = computed(() => {
  const s = caseData.value?.payment_status;
  if (s === 'paid') return 'bg-green-50 text-green-700';
  if (s === 'pending') return 'bg-amber-50 text-amber-600';
  return 'bg-gray-50 text-gray-400';
});
const paymentLabel = computed(() => {
  const s = caseData.value?.payment_status;
  if (s === 'paid') return 'Оплачено';
  if (s === 'pending') return 'Ожидает оплаты';
  return 'Не оплачено';
});

function stepClass(idx) {
  if (stageIdx.value > idx) return 'bg-blue-500 text-white';
  if (stageIdx.value === idx) return 'bg-blue-600 text-white ring-4 ring-blue-100';
  return 'bg-gray-200 text-gray-400';
}

// Helpers
function cleanPhone(p) { return (p ?? '').replace(/[^0-9]/g, ''); }
function fmtFull(d) { return new Date(d).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }); }
function fmtShort(d) { return new Date(d).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' }); }
function isImage(m) { return m?.startsWith('image/'); }
function isPdf(m) { return m === 'application/pdf'; }

// Data
async function load() {
  loading.value = true;
  try {
    const [c, cl] = await Promise.all([casesApi.get(id), casesApi.getChecklist(id)]);
    caseData.value = c.data.data;
    checklist.value = cl.data.data;
  } finally { loading.value = false; }
}
async function reloadChecklist() { checklist.value = (await casesApi.getChecklist(id)).data.data; }
async function reloadAll() {
  const [c, cl] = await Promise.all([casesApi.get(id), casesApi.getChecklist(id)]);
  caseData.value = c.data.data;
  checklist.value = cl.data.data;
}

// Actions
async function quickMove(stage) { await casesApi.moveStage(id, { stage }); await reloadAll(); }

async function uploadToSlot(item, event) {
  const file = event.target?.files?.[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('file', file);
  await casesApi.uploadToSlot(id, item.id, fd);
  await reloadAll();
}

async function toggleCheck(item) { await casesApi.checkSlot(id, item.id, !item.is_checked); await reloadChecklist(); }

function openReject(item) { rejectItem.value = item; rejectNote.value = ''; showRejectModal.value = true; }
async function submitReject() { await casesApi.reviewSlot(id, rejectItem.value.id, { status: 'rejected', notes: rejectNote.value }); showRejectModal.value = false; await reloadAll(); }
async function reviewSlot(item, status) { await casesApi.reviewSlot(id, item.id, { status }); await reloadAll(); }

function openTranslation(item) { translationItem.value = item; translationForm.pages = 1; translationForm.notes = ''; showTranslationModal.value = true; }
async function submitTranslation() {
  await casesApi.reviewSlot(id, translationItem.value.id, { status: 'needs_translation', notes: translationForm.notes || null, translation_pages: parseInt(translationForm.pages) || 1 });
  showTranslationModal.value = false; await reloadAll();
}

async function doUploadTranslation(item, event) {
  const file = event.target?.files?.[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('file', file);
  await casesApi.uploadTranslation(id, item.id, fd);
  await reloadAll();
}

async function doApproveTranslation(item) { await casesApi.approveTranslation(id, item.id); await reloadAll(); }

async function doSubmitToEmbassy() {
  if (!embassyForm.submitted_at || !embassyForm.expected_result_date) return;
  embassyForm.loading = true;
  try { await casesApi.submitToEmbassy(id, { submitted_at: embassyForm.submitted_at, expected_result_date: embassyForm.expected_result_date }); await reloadAll(); }
  finally { embassyForm.loading = false; }
}

async function doComplete() {
  if (!resultForm.result_type) return;
  resultForm.loading = true;
  try {
    await casesApi.complete(id, {
      result_type: resultForm.result_type, result_notes: resultForm.result_notes || null,
      visa_issued_at: resultForm.visa_issued_at || null, visa_received_at: resultForm.visa_received_at || null,
      visa_validity: resultForm.visa_validity || null, rejection_reason: resultForm.rejection_reason || null,
      can_reapply: resultForm.can_reapply, reapply_recommendation: resultForm.reapply_recommendation || null,
    });
    showResultModal.value = false; await reloadAll();
  } finally { resultForm.loading = false; }
}

async function doUpdateExpectedDate() {
  if (!expectedDateForm.expected_result_date) return;
  expectedDateForm.loading = true;
  try { await casesApi.updateExpectedDate(id, { expected_result_date: expectedDateForm.expected_result_date, notes: expectedDateForm.notes || null }); showExpectedDateModal.value = false; await reloadAll(); }
  finally { expectedDateForm.loading = false; }
}

async function repeatSlot(item) { await casesApi.addChecklistItem(id, { name: item.name, description: item.description, is_required: false }); await reloadChecklist(); }

async function addSlot() {
  if (!newSlot.name) return;
  newSlot.loading = true;
  try { await casesApi.addChecklistItem(id, { name: newSlot.name, description: newSlot.description, is_required: newSlot.is_required }); showAddSlot.value = false; Object.assign(newSlot, { name: '', description: '', is_required: false }); await reloadChecklist(); }
  finally { newSlot.loading = false; }
}

async function deleteSlot(item) { if (!confirm('Удалить?')) return; await casesApi.deleteChecklistItem(id, item.id); await reloadChecklist(); }

async function downloadZip() {
  zipLoading.value = true;
  try { const r = await casesApi.downloadAllZip(id); const u = URL.createObjectURL(new Blob([r.data], { type: 'application/zip' })); const a = document.createElement('a'); a.href = u; a.download = `docs-${id.slice(0, 8)}.zip`; a.click(); URL.revokeObjectURL(u); }
  finally { zipLoading.value = false; }
}

function openPreview(doc) { preview.value = doc; }

async function doMoveStage() {
  if (!moveForm.stage) return;
  moveForm.loading = true;
  try { await casesApi.moveStage(id, { stage: moveForm.stage, notes: moveForm.notes || null }); showMoveModal.value = false; await load(); }
  finally { moveForm.loading = false; }
}

async function confirmDelete() { if (!confirm('Удалить заявку?')) return; await casesApi.remove(id); router.push({ name: 'cases' }); }

async function loadManagers() {
  if (!auth.isOwner) return;
  try {
    const { data } = await usersApi.list();
    const list = Array.isArray(data.data) ? data.data : (data.data?.data ?? []);
    managers.value = list.filter(u => u.role === 'manager' || u.role === 'owner');
  } catch {}
}

async function doAssign() {
  if (!assignForm.manager_id) return;
  assignForm.loading = true;
  try {
    await casesApi.update(id, { assigned_to: assignForm.manager_id });
    await reloadAll();
  } finally { assignForm.loading = false; }
}

async function doAssignFromModal() {
  if (!assignForm.manager_id) return;
  assignForm.loading = true;
  try {
    await casesApi.update(id, { assigned_to: assignForm.manager_id });
    showAssignModal.value = false;
    await reloadAll();
  } finally { assignForm.loading = false; }
}

onMounted(() => { load(); loadManagers(); });
</script>

<style scoped>
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
</style>
