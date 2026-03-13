<template>
  <!-- Loading -->
  <div v-if="loading" class="flex items-center justify-center py-32">
    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
  </div>

  <div v-else-if="loadError" class="flex flex-col items-center justify-center py-32 text-gray-500">
    <p class="text-sm mb-3">{{ loadError }}</p>
    <button @click="load" class="text-sm text-blue-600 hover:underline">{{ t('crm.caseDetail.retry') }}</button>
  </div>

  <div v-else-if="caseData">

    <!-- Toast -->
    <transition name="fade">
      <div v-if="toast.msg" :class="['fixed bottom-6 left-1/2 -translate-x-1/2 z-50 text-white text-sm font-medium px-5 py-3 rounded-xl shadow-lg flex items-center gap-2 pointer-events-none',
        toast.type === 'error' ? 'bg-red-500' : 'bg-green-500']">
        {{ toast.msg }}
      </div>
    </transition>

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
        <button @click="showMoveModal = true" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 font-medium transition-colors">{{ t('crm.caseDetail.changeStage') }}</button>
        <button @click="confirmDelete" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-400 hover:bg-red-50 font-medium transition-colors">{{ t('crm.caseDetail.deleteCase') }}</button>
      </div>
    </div>

    <!-- ===== STAGE STEPPER ===== -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 mb-4 overflow-x-auto scrollbar-hide">
      <div class="flex items-center min-w-[560px]">
        <template v-for="(st, idx) in STAGES" :key="st.key">
          <!-- Connector line -->
          <div v-if="idx > 0" class="flex-1 h-0.5 mx-0.5 rounded-full transition-colors duration-500" :class="stageIdx >= idx ? 'bg-blue-500' : 'bg-gray-200'"></div>
          <!-- Step circle + label -->
          <button class="flex flex-col items-center gap-1 shrink-0 group" :disabled="!canMoveTo(st.key)" @click="canMoveTo(st.key) && (moveForm.stage = st.key, showMoveModal = true)">
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
        <a v-if="caseData.client?.phone" :href="`tel:${caseData.client.phone}`" class="text-xs text-gray-400 hover:text-blue-600">{{ formatPhone(caseData.client.phone) }}</a>
      </div>
      <div v-if="caseData.client?.phone" class="flex gap-2 shrink-0 ml-3">
        <a :href="'https://t.me/+' + cleanPhone(caseData.client.phone)" target="_blank" class="text-xs px-3 py-1.5 rounded-lg bg-sky-50 text-sky-600 font-medium">TG</a>
        <a :href="'https://wa.me/' + cleanPhone(caseData.client.phone)" target="_blank" class="text-xs px-3 py-1.5 rounded-lg bg-green-50 text-green-600 font-medium">WA</a>
        <a :href="'tel:' + caseData.client.phone" class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 font-medium">{{ t('crm.caseDetail.call') }}</a>
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
              <span class="text-[10px] font-bold uppercase tracking-widest text-orange-500">{{ t('crm.caseDetail.ownerActionRequired') }}</span>
            </div>
            <h2 class="text-base font-bold text-gray-900 mb-3">{{ t('crm.caseDetail.assignManagerTitle') }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ t('crm.caseDetail.assignManagerDesc') }}</p>

            <div class="flex items-end gap-3">
              <div class="flex-1 max-w-xs">
                <SearchSelect
                  v-model="assignForm.manager_id"
                  :items="managerItems"
                  :label="t('crm.caseDetail.managerLabel')"
                  :placeholder="t('crm.caseDetail.selectManager')"
                  allow-all
                  :all-label="t('crm.caseDetail.selectManager')"
                />
              </div>
              <button @click="doAssign" :disabled="!assignForm.manager_id || assignForm.loading"
                class="px-5 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white text-sm font-semibold shadow-md shadow-blue-200 hover:shadow-lg active:scale-[0.98] transition-all duration-200 disabled:opacity-50">
                {{ assignForm.loading ? t('crm.caseDetail.assigning') : t('crm.caseDetail.assign') }}
              </button>
            </div>

            <!-- SLA/deadline info for owner -->
            <div v-if="caseData.critical_date || slaInfo" class="mt-4 pt-3 border-t border-orange-100 flex items-center gap-4 flex-wrap">
              <div v-if="slaInfo" class="flex items-center gap-2">
                <span :class="['text-xs font-bold', slaInfo.value]">SLA: {{ slaInfo.display }}</span>
                <span :class="['text-xs', slaInfo.sub]">{{ slaInfo.subText }}</span>
              </div>
              <div v-if="caseData.critical_date" class="flex items-center gap-2">
                <span class="text-xs text-gray-400">{{ t('crm.caseDetail.deadlineLabel') }}</span>
                <span :class="['text-xs font-bold', deadlineClass]">{{ fmtShort(caseData.critical_date) }}</span>
              </div>
              <div v-if="caseData.days_left != null" class="flex items-center gap-2">
                <span :class="['text-xs font-bold', caseData.days_left < 0 ? 'text-red-600' : caseData.days_left <= 7 ? 'text-yellow-600' : 'text-green-600']">
                  {{ caseData.days_left < 0 ? t('crm.caseDetail.overdueDays', { n: Math.abs(caseData.days_left) }) : t('crm.caseDetail.daysRemaining', { n: caseData.days_left }) }}
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
              {{ t('crm.caseDetail.ctaLeadToQualification') }}
            </button>

            <!-- qualification -->
            <button v-if="caseData.stage === 'qualification'" @click="quickMove('documents')"
              class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white text-sm font-semibold shadow-md shadow-blue-200 hover:shadow-lg hover:shadow-blue-300 active:scale-[0.98] transition-all duration-200">
              {{ t('crm.caseDetail.ctaQualificationToDocs') }}
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
                {{ t('crm.caseDetail.ctaDocsToReview') }}
              </button>
              <p v-else class="text-xs text-gray-400">{{ t('crm.caseDetail.waitingUpload') }}</p>
            </div>

            <!-- doc_review -->
            <div v-if="caseData.stage === 'doc_review'">
              <div class="flex items-center gap-4 text-xs text-gray-500 mb-2">
                <span>{{ t('crm.caseDetail.reviewedLabel') }} <strong class="text-gray-800">{{ reviewedCount }}</strong> / {{ totalDocsCount }}</span>
                <span v-if="needsTranslationCount > 0" class="text-purple-600 font-medium">{{ t('crm.caseDetail.forTranslation', { n: needsTranslationCount }) }}</span>
              </div>
              <div class="flex gap-2">
                <button v-if="needsTranslationCount > 0 && allReviewed" @click="quickMove('translation')"
                  class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-purple-500 text-white text-sm font-semibold shadow-md shadow-purple-200 hover:shadow-lg active:scale-[0.98] transition-all duration-200">
                  {{ t('crm.caseDetail.translationNeeded') }}
                </button>
                <button v-if="needsTranslationCount === 0 && allReviewed" @click="quickMove('ready')"
                  class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-green-600 to-green-500 text-white text-sm font-semibold shadow-md shadow-green-200 hover:shadow-lg active:scale-[0.98] transition-all duration-200">
                  {{ t('crm.caseDetail.allOkToSubmission') }}
                </button>
              </div>
            </div>

            <!-- translation -->
            <div v-if="caseData.stage === 'translation'">
              <div class="flex items-center gap-3 text-xs text-gray-500 mb-2">
                <span>{{ t('crm.caseDetail.translatedLabel') }} <strong class="text-gray-800">{{ translatedCount }}</strong> / {{ needsTranslationCount }}</span>
              </div>
              <button v-if="allTranslated" @click="quickMove('ready')"
                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-green-600 to-green-500 text-white text-sm font-semibold shadow-md shadow-green-200 hover:shadow-lg active:scale-[0.98] transition-all duration-200">
                {{ t('crm.caseDetail.allTranslationsReady') }}
              </button>
            </div>

            <!-- ready -->
            <div v-if="caseData.stage === 'ready'">
              <div class="grid grid-cols-2 gap-3 mb-3">
                <AppInput v-model="embassyForm.submitted_at" type="date" :label="t('crm.caseDetail.submissionDateLabel')" />
                <AppInput v-model="embassyForm.expected_result_date" type="date" :label="t('crm.caseDetail.expectedResultLabel')" />
              </div>
              <AppButton :loading="embassyForm.loading" size="sm" @click="doSubmitToEmbassy">{{ t('crm.caseDetail.markSubmission') }}</AppButton>
            </div>

            <!-- review -->
            <div v-if="caseData.stage === 'review'">
              <div class="flex items-center gap-4 text-xs text-gray-500 mb-3 flex-wrap">
                <span v-if="caseData.submitted_at">{{ t('crm.caseDetail.submittedAt') }} <strong>{{ fmtShort(caseData.submitted_at) }}</strong></span>
                <span v-if="caseData.expected_result_date">{{ t('crm.caseDetail.expectedAt') }} <strong>{{ fmtShort(caseData.expected_result_date) }}</strong></span>
                <span v-if="daysUntilResult !== null" :class="daysUntilResult < 0 ? 'text-red-600 font-bold' : ''">
                  {{ daysUntilResult < 0 ? t('crm.caseDetail.overdueDaysResult', { n: Math.abs(daysUntilResult) }) : t('crm.caseDetail.daysUntilResult', { n: daysUntilResult }) }}
                </span>
              </div>
              <div class="flex gap-2">
                <AppButton size="sm" @click="showResultModal = true">{{ t('crm.caseDetail.recordResult') }}</AppButton>
                <AppButton variant="outline" size="sm" @click="showExpectedDateModal = true">{{ t('crm.caseDetail.changeDateBtn') }}</AppButton>
              </div>
            </div>

            <!-- result -->
            <div v-if="caseData.stage === 'result'" :class="['rounded-lg p-4', caseData.result_type === 'approved' ? 'bg-green-50' : 'bg-red-50']">
              <p :class="['font-bold text-sm mb-1', caseData.result_type === 'approved' ? 'text-green-800' : 'text-red-800']">
                {{ caseData.result_type === 'approved' ? t('crm.caseDetail.visaApproved') : t('crm.caseDetail.visaRejected') }}
              </p>
              <div :class="['text-xs space-y-0.5', caseData.result_type === 'approved' ? 'text-green-700' : 'text-red-700']">
                <p v-if="caseData.result_notes">{{ caseData.result_notes }}</p>
                <p v-if="caseData.visa_issued_at">{{ t('crm.caseDetail.issuedAt', { date: fmtShort(caseData.visa_issued_at) }) }}</p>
                <p v-if="caseData.visa_received_at">{{ t('crm.caseDetail.receivedAt', { date: fmtShort(caseData.visa_received_at) }) }}</p>
                <p v-if="caseData.visa_validity">{{ t('crm.caseDetail.validityPeriod', { value: caseData.visa_validity }) }}</p>
                <p v-if="caseData.rejection_reason">{{ t('crm.caseDetail.rejectionReason', { value: caseData.rejection_reason }) }}</p>
                <p v-if="caseData.can_reapply !== null && caseData.can_reapply !== undefined">{{ t('crm.caseDetail.reapplyLabel', { value: caseData.can_reapply ? t('common.yes') : t('common.no') }) }}</p>
                <p v-if="caseData.reapply_recommendation">{{ t('crm.caseDetail.recommendationLabel', { value: caseData.reapply_recommendation }) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== FORM WIZARD (Visa Case Engine) ===== -->
        <FormWizard v-if="caseData.visa_case_rule_id && caseData.embassy_platform"
          ref="formWizardRef"
          :case-id="caseData.id"
          @updated="onEngineUpdate" />

        <!-- ===== DOCUMENTS ===== -->
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
          <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50">
            <div class="flex items-center gap-3">
              <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.caseDetail.documentsTitle') }}</h3>
              <span v-if="checklist.progress" class="text-xs text-gray-400 tabular-nums">
                {{ t('crm.caseDetail.uploadedOf', { uploaded: checklist.progress.uploaded, total: checklist.progress.total }) }}
              </span>
            </div>
            <div class="flex items-center gap-2">
              <button v-if="uploadedCount > 0" @click="downloadZip" :disabled="zipLoading"
                class="text-xs text-gray-500 hover:text-gray-700 px-2.5 py-1 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                {{ zipLoading ? 'ZIP...' : t('crm.caseDetail.downloadZip') }}
              </button>
              <button @click="showAddSlot = true"
                class="text-xs text-blue-600 hover:text-blue-700 px-2.5 py-1 rounded-lg hover:bg-blue-50 font-medium transition-colors">
                {{ t('crm.caseDetail.addDocument') }}
              </button>
            </div>
          </div>

          <!-- Person tabs -->
          <div v-if="docTabs.length > 1" class="px-5 pt-3 flex gap-1 overflow-x-auto scrollbar-hide">
            <button v-for="tab in docTabs" :key="tab.key"
              @click="activeDocTab = tab.key"
              class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap"
              :class="activeDocTab === tab.key
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
              {{ tab.label }}
              <span class="ml-1 text-[10px] opacity-70">{{ tab.uploaded }}/{{ tab.total }}</span>
            </button>
          </div>

          <!-- Progress -->
          <div v-if="activeTabItems.length" class="px-5 pt-3">
            <div class="w-full bg-gray-100 rounded-full h-1 overflow-hidden">
              <div class="h-full rounded-full transition-all duration-700 ease-out"
                :class="activeTabUploaded >= activeTabItems.length ? 'bg-green-500' : 'bg-blue-500'"
                :style="{ width: (activeTabItems.length ? (activeTabUploaded / activeTabItems.length * 100) : 0) + '%' }"></div>
            </div>
          </div>

          <!-- Action-needed -->
          <div v-if="activeTabAction.length" class="px-5 pt-4">
            <p class="text-[10px] uppercase tracking-widest font-bold text-orange-500 mb-2">{{ t('crm.caseDetail.actionNeeded', { n: activeTabAction.length }) }}</p>
            <div class="space-y-2">
              <DocItem v-for="item in activeTabAction" :key="item.id" :item="item"
                :ai-loading="aiAnalyzingId === item.id"
                @upload="uploadToSlot" @toggle="toggleCheck" @review="reviewSlot"
                @reject="openReject" @translation="openTranslation"
                @upload-translation="doUploadTranslation" @approve-translation="doApproveTranslation"
                @preview="openPreview" @delete="deleteSlot" @repeat="repeatSlot"
                @ai-analyze="doAiAnalyze" />
            </div>
          </div>

          <!-- Done docs -->
          <div v-if="activeTabDone.length" class="px-5 pt-4">
            <p v-if="activeTabAction.length" class="text-[10px] uppercase tracking-widest font-bold text-gray-300 mb-2">{{ t('crm.caseDetail.doneItems', { n: activeTabDone.length }) }}</p>
            <div class="space-y-2">
              <DocItem v-for="item in activeTabDone" :key="item.id" :item="item"
                :ai-loading="aiAnalyzingId === item.id"
                @upload="uploadToSlot" @toggle="toggleCheck" @review="reviewSlot"
                @reject="openReject" @translation="openTranslation"
                @upload-translation="doUploadTranslation" @approve-translation="doApproveTranslation"
                @preview="openPreview" @delete="deleteSlot" @repeat="repeatSlot"
                @ai-analyze="doAiAnalyze" />
            </div>
          </div>

          <p v-if="!checklist.items?.length" class="text-sm text-gray-400 py-8 text-center">{{ t('crm.caseDetail.checklistEmpty') }}</p>
          <div class="h-4"></div>
        </div>

        <!-- ===== AI RISK DASHBOARD ===== -->
        <CaseAiDashboard :case-id="id" ref="aiDashboardRef" />

        <!-- ===== TIMELINE (collapsed) ===== -->
        <div class="bg-white rounded-xl border border-gray-100">
          <button @click="timelineOpen = !timelineOpen"
            class="flex items-center justify-between w-full px-5 py-3 text-left hover:bg-gray-50/50 transition-colors rounded-xl">
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.caseDetail.historyTitle') }}</h3>
              <span class="text-xs text-gray-300">{{ t('crm.caseDetail.historyEntries', { n: caseData.stage_history?.length ?? 0 }) }}</span>
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
                <p class="text-xs text-gray-400">{{ fmtFull(h.entered_at) }} · {{ h.user?.name ?? t('crm.caseDetail.systemUser') }}</p>
                <p v-if="h.notes" class="text-xs text-gray-500 mt-0.5">{{ h.notes }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT: Sidebar (4/12) -->
      <div class="lg:col-span-4 space-y-4">

        <!-- Client Portrait -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 space-y-3">
          <!-- Header: name + type badge -->
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">{{ t('crm.caseDetail.clientSidebarTitle') }}</p>
              <RouterLink :to="{ name: 'clients.show', params: { id: caseData.client?.id } }"
                class="text-sm font-bold text-gray-900 hover:text-blue-600 transition-colors block truncate">
                {{ caseData.client?.name ?? '---' }}
              </RouterLink>
            </div>
            <span v-if="portrait" :class="['text-[10px] font-bold px-2 py-0.5 rounded-full whitespace-nowrap shrink-0', portrait.total_cases > 1 ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500']">
              {{ portrait.total_cases > 1 ? t('crm.caseDetail.portraitReturning', { n: portrait.total_cases }) : t('crm.caseDetail.portraitFirstClient') }}
            </span>
          </div>

          <!-- Age + Nationality row -->
          <div v-if="clientAge || caseData.client?.nationality" class="flex items-center gap-2 flex-wrap">
            <span v-if="clientAge" class="text-xs text-gray-600 font-medium">{{ t('crm.caseDetail.portraitAge', { n: clientAge }) }}</span>
            <span v-if="clientAge && caseData.client?.nationality" class="text-gray-300">|</span>
            <span v-if="caseData.client?.nationality" class="text-xs text-gray-600">{{ caseData.client.nationality }}</span>
          </div>

          <!-- Case stats (approved / rejected) -->
          <div v-if="portrait && (portrait.approved_cases || portrait.rejected_cases)" class="flex items-center gap-3">
            <span v-if="portrait.approved_cases" class="text-xs font-semibold text-green-600">{{ portrait.approved_cases }} {{ t('crm.caseDetail.portraitApproved') }}</span>
            <span v-if="portrait.rejected_cases" class="text-xs font-semibold text-red-500">{{ portrait.rejected_cases }} {{ t('crm.caseDetail.portraitRejected') }}</span>
          </div>

          <!-- Contact buttons -->
          <div class="flex items-center gap-2">
            <a v-if="caseData.client?.phone" :href="`tel:${caseData.client.phone}`" class="text-xs text-gray-500 hover:text-blue-600">{{ formatPhone(caseData.client.phone) }}</a>
            <span v-if="caseData.client?.phone && caseData.client?.email" class="text-gray-300">|</span>
            <span v-if="caseData.client?.email" class="text-xs text-gray-500">{{ caseData.client.email }}</span>
          </div>
          <div v-if="caseData.client?.phone" class="flex gap-2">
            <a :href="'https://t.me/+' + cleanPhone(caseData.client.phone)" target="_blank"
              class="text-xs px-3 py-1.5 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 font-medium transition-colors">Telegram</a>
            <a :href="'https://wa.me/' + cleanPhone(caseData.client.phone)" target="_blank"
              class="text-xs px-3 py-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 font-medium transition-colors">{{ t('crm.caseDetail.whatsappBtn') }}</a>
            <a :href="'tel:' + caseData.client.phone"
              class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 font-medium transition-colors">{{ t('crm.caseDetail.callBtn') }}</a>
          </div>

          <!-- Passport -->
          <div v-if="portrait?.passport_number" class="pt-2 border-t border-gray-50">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-gray-400 uppercase font-bold">{{ t('crm.caseDetail.portraitPassport') }}</span>
              <span v-if="passportWarning" :class="['text-[10px] font-bold', passportWarning.cls]">{{ passportWarning.text }}</span>
            </div>
            <p class="text-xs text-gray-700 font-mono mt-0.5">{{ portrait.passport_number }}
              <span v-if="portrait.passport_expires_at" class="text-gray-400 font-sans ml-1">{{ t('crm.caseDetail.portraitPassport') }}: {{ fmtShort(portrait.passport_expires_at) }}</span>
            </p>
          </div>

          <!-- Scoring for this case country -->
          <div v-if="countryScore" class="pt-2 border-t border-gray-50">
            <div class="flex items-center justify-between mb-1">
              <span class="text-[10px] text-gray-400 uppercase font-bold">{{ t('crm.caseDetail.portraitScoring') }}</span>
              <span :class="['text-lg font-black tabular-nums', scoreColor(countryScore.score)]">{{ countryScore.score }}</span>
            </div>
            <div class="bg-gray-200 rounded-full h-1.5 overflow-hidden">
              <div :class="['h-full rounded-full transition-all', scoreBarColor(countryScore.score)]" :style="{ width: `${countryScore.score}%` }"></div>
            </div>
            <p class="text-[10px] text-gray-500 mt-0.5">{{ countryScore.level_label }}</p>
          </div>

          <!-- Profile sections (only if profile exists) -->
          <template v-if="profile">

            <!-- Family -->
            <div class="pt-2 border-t border-gray-50">
              <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">{{ t('crm.caseDetail.portraitFamily') }}</p>
              <div class="flex flex-wrap gap-x-3 gap-y-0.5">
                <span class="text-xs text-gray-700">{{ maritalLabel }}</span>
                <span v-if="profile.marital_status === 'married' && profile.spouse_employed" class="text-xs text-green-600">{{ t('crm.caseDetail.portraitSpouseWorks') }}</span>
                <span class="text-xs text-gray-700">
                  <template v-if="profile.children_count > 0">
                    {{ t('crm.caseDetail.portraitChildren', { n: profile.children_count }) }}
                    <span v-if="profile.children_staying_home" class="text-green-600">({{ t('crm.caseDetail.portraitChildrenHome') }})</span>
                  </template>
                  <template v-else>{{ t('crm.caseDetail.portraitNoChildren') }}</template>
                </span>
              </div>
            </div>

            <!-- Work -->
            <div class="pt-2 border-t border-gray-50">
              <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">{{ t('crm.caseDetail.portraitWork') }}</p>
              <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                  <span :class="['text-xs font-medium', employmentColor]">{{ employmentLabel }}</span>
                  <span v-if="profile.position" class="text-xs text-gray-500">{{ profile.position }}</span>
                </div>
                <p v-if="profile.employer_name" class="text-xs text-gray-500">{{ profile.employer_name }}</p>
                <p v-if="profile.years_at_current_job" class="text-xs text-gray-400">{{ t('crm.caseDetail.portraitYearsJob', { n: profile.years_at_current_job }) }}</p>
              </div>
            </div>

            <!-- Finance -->
            <div class="pt-2 border-t border-gray-50">
              <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">{{ t('crm.caseDetail.portraitFinance') }}</p>
              <div class="grid grid-cols-2 gap-1">
                <div v-if="profile.monthly_income">
                  <p class="text-[10px] text-gray-400">{{ t('crm.caseDetail.portraitIncome') }}</p>
                  <p class="text-xs font-semibold text-gray-800">${{ fmtMoney(profile.monthly_income) }}</p>
                </div>
                <div v-if="profile.bank_balance">
                  <p class="text-[10px] text-gray-400">{{ t('crm.caseDetail.portraitBank') }}</p>
                  <p class="text-xs font-semibold text-gray-800">${{ fmtMoney(profile.bank_balance) }}</p>
                </div>
                <div v-if="profile.has_fixed_deposit && profile.fixed_deposit_amount">
                  <p class="text-[10px] text-gray-400">{{ t('crm.caseDetail.portraitDeposit') }}</p>
                  <p class="text-xs font-semibold text-gray-800">${{ fmtMoney(profile.fixed_deposit_amount) }}</p>
                </div>
              </div>
            </div>

            <!-- Assets -->
            <div class="pt-2 border-t border-gray-50">
              <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">{{ t('crm.caseDetail.portraitAssets') }}</p>
              <div v-if="profile.has_real_estate || profile.has_car || profile.has_business" class="flex flex-wrap gap-1.5">
                <span v-if="profile.has_real_estate" class="text-[10px] px-2 py-0.5 rounded-full bg-green-50 text-green-700 font-medium">{{ t('crm.caseDetail.portraitRealEstate') }}</span>
                <span v-if="profile.has_car" class="text-[10px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-medium">{{ t('crm.caseDetail.portraitCar') }}</span>
                <span v-if="profile.has_business" class="text-[10px] px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 font-medium">{{ t('crm.caseDetail.portraitBusiness') }}</span>
              </div>
              <p v-else class="text-xs text-gray-400">{{ t('crm.caseDetail.portraitNoAssets') }}</p>
            </div>

            <!-- Visa History -->
            <div class="pt-2 border-t border-gray-50">
              <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">{{ t('crm.caseDetail.portraitVisaHistory') }}</p>
              <div v-if="profile.has_schengen_visa || profile.has_us_visa || profile.has_uk_visa" class="flex flex-wrap gap-1.5 mb-1">
                <span v-if="profile.has_schengen_visa" class="text-[10px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-medium">{{ t('crm.caseDetail.portraitSchengen') }}</span>
                <span v-if="profile.has_us_visa" class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-medium">{{ t('crm.caseDetail.portraitUSA') }}</span>
                <span v-if="profile.has_uk_visa" class="text-[10px] px-2 py-0.5 rounded-full bg-red-50 text-red-700 font-medium">{{ t('crm.caseDetail.portraitUK') }}</span>
              </div>
              <div v-else class="mb-1">
                <span class="text-xs text-gray-400">{{ t('crm.caseDetail.portraitNoVisas') }}</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <span v-if="profile.previous_refusals > 0" class="text-xs font-semibold text-red-500">{{ t('crm.caseDetail.portraitRefusals', { n: profile.previous_refusals }) }}</span>
                <span v-else class="text-xs text-green-600">{{ t('crm.caseDetail.portraitCleanHistory') }}</span>
                <span v-if="profile.has_overstay" class="text-xs font-semibold text-red-600">{{ t('crm.caseDetail.portraitOverstay') }}</span>
              </div>
            </div>

            <!-- Education -->
            <div v-if="profile.education_level" class="pt-2 border-t border-gray-50">
              <div class="flex items-center justify-between">
                <span class="text-[10px] text-gray-400 uppercase font-bold">{{ t('crm.caseDetail.portraitEducation') }}</span>
                <span class="text-xs text-gray-700">{{ educationLabel }}</span>
              </div>
            </div>

          </template>

          <!-- No profile hint -->
          <div v-else-if="!portraitLoading" class="pt-2 border-t border-gray-50">
            <p class="text-xs text-gray-400">{{ t('crm.caseDetail.portraitNoProfile') }}</p>
          </div>

          <!-- View full profile link -->
          <div v-if="caseData.client?.id" class="pt-2 border-t border-gray-50">
            <RouterLink :to="{ name: 'clients.show', params: { id: caseData.client.id } }"
              class="text-xs text-blue-500 hover:text-blue-700 font-medium">
              {{ t('crm.caseDetail.portraitViewProfile') }}
            </RouterLink>
          </div>
        </div>

        <!-- Visa Case Engine: Readiness -->
        <ReadinessPanel v-if="hasEngine || caseData.visa_case_rule_id"
          ref="readinessPanelRef"
          :case-id="caseData.id"
          :has-rule="!!caseData.visa_case_rule_id"
          @initialized="onEngineInit"
          @updated="onEngineUpdate" />

        <!-- Visa Case Engine: Checkpoints -->
        <CheckpointsList v-if="caseData.visa_case_rule_id"
          ref="checkpointsListRef"
          :case-id="caseData.id"
          @updated="onEngineUpdate" />

        <!-- Visa Case Engine: Guidance -->
        <CaseGuidancePanel v-if="caseData.visa_case_rule_id"
          ref="guidancePanelRef"
          :case-id="caseData.id"
          :stage="caseData.stage" />

        <!-- Case meta -->
        <div class="bg-white rounded-xl border border-gray-100 p-4">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">{{ t('crm.caseDetail.infoTitle') }}</p>
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-400">{{ t('crm.caseDetail.stageField') }}</span>
              <AppBadge :color="stageColor">{{ stageLabel }}</AppBadge>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-400">{{ t('crm.caseDetail.priorityField') }}</span>
              <AppBadge :color="priorityColor">{{ priorityLabel }}</AppBadge>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-400">{{ t('crm.caseDetail.deadlineField') }}</span>
              <span :class="['text-xs font-semibold', deadlineClass]">{{ caseData.critical_date ? fmtShort(caseData.critical_date) : '---' }}</span>
            </div>
            <div v-if="caseData.days_left != null" class="flex items-center justify-between">
              <span class="text-xs text-gray-400">{{ t('crm.caseDetail.daysLeftField') }}</span>
              <span :class="['text-xs font-bold', caseData.days_left < 0 ? 'text-red-600' : caseData.days_left <= 7 ? 'text-yellow-600' : 'text-gray-700']">{{ caseData.days_left }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-400">{{ t('crm.caseDetail.managerField') }}</span>
              <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-700 font-medium">{{ caseData.assignee?.name ?? '---' }}</span>
                <button v-if="isOwner" @click="showAssignModal = true"
                  class="text-[10px] text-blue-500 hover:text-blue-700 font-medium">
                  {{ caseData.assignee ? t('crm.caseDetail.editShort') : t('crm.caseDetail.assignShort') }}
                </button>
              </div>
            </div>
            <div v-if="caseData.appointment_date" class="flex items-center justify-between">
              <span class="text-xs text-gray-400">{{ t('crm.caseDetail.appointmentField') }}</span>
              <span class="text-xs text-green-600 font-medium">{{ caseData.appointment_date }}{{ caseData.appointment_time ? ' ' + caseData.appointment_time : '' }}</span>
            </div>
            <div v-else-if="['qualification','documents','doc_review','translation','ready'].includes(caseData.stage)"
              class="flex items-center gap-1.5 p-2 rounded-lg bg-amber-50 border border-amber-200">
              <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.168 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
              <span class="text-[10px] text-amber-700 font-medium">{{ t('crm.caseDetail.appointmentWarning') }}</span>
            </div>
            <div v-if="caseData.submitted_at" class="flex items-center justify-between">
              <span class="text-xs text-gray-400">{{ t('crm.caseDetail.submittedField') }}</span>
              <span class="text-xs text-gray-700">{{ fmtShort(caseData.submitted_at) }}</span>
            </div>
            <div v-if="caseData.expected_result_date" class="flex items-center justify-between">
              <span class="text-xs text-gray-400">{{ t('crm.caseDetail.expectedField') }}</span>
              <span class="text-xs text-gray-700">{{ fmtShort(caseData.expected_result_date) }}</span>
            </div>
          </div>
        </div>

        <!-- Payment -->
        <div v-if="caseData.payment_status" class="bg-white rounded-xl border border-gray-100 p-4">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ t('crm.caseDetail.paymentTitle') }}</p>
          <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', paymentBadgeClass]">{{ paymentLabel }}</span>
          <p v-if="caseData.total_amount" class="text-lg font-black text-gray-900 mt-2">
            {{ Number(caseData.total_amount).toLocaleString() }} <span class="text-xs font-normal text-gray-400">{{ t('crm.caseDetail.sumLabel') }}</span>
          </p>
        </div>

        <!-- Quick actions -->
        <div class="bg-white rounded-xl border border-gray-100 p-4">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ t('crm.caseDetail.actionsTitle') }}</p>
          <div class="space-y-1">
            <button @click="showMoveModal = true" class="w-full text-left text-xs px-3 py-2 rounded-lg hover:bg-gray-50 text-gray-600 transition-colors">{{ t('crm.caseDetail.actionChangeStage') }}</button>
            <button @click="showAddSlot = true" class="w-full text-left text-xs px-3 py-2 rounded-lg hover:bg-gray-50 text-gray-600 transition-colors">{{ t('crm.caseDetail.actionAddDocument') }}</button>
            <button v-if="uploadedCount > 0" @click="downloadZip" class="w-full text-left text-xs px-3 py-2 rounded-lg hover:bg-gray-50 text-gray-600 transition-colors">{{ t('crm.caseDetail.actionDownloadAll') }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== MODALS ===== -->
  <AppModal v-model="showMoveModal" :title="t('crm.caseDetail.moveStageModalTitle')">
    <div class="space-y-4">
      <AppSelect v-model="moveForm.stage" :options="stageOptions" :label="t('crm.caseDetail.newStageLabel')" />
      <AppInput v-model="moveForm.notes" :label="t('crm.caseDetail.commentLabel')" :placeholder="t('crm.caseDetail.commentPlaceholder')" />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showMoveModal = false">{{ t('common.cancel') }}</AppButton>
        <AppButton :loading="moveForm.loading" @click="doMoveStage">{{ t('crm.caseDetail.moveBtn') }}</AppButton>
      </div>
    </div>
  </AppModal>

  <AppModal v-model="showAddSlot" :title="t('crm.caseDetail.addDocumentModalTitle')">
    <div class="space-y-4">
      <AppInput v-model="newSlot.name" :label="t('crm.caseDetail.docNameLabel')" :placeholder="t('crm.caseDetail.docNamePlaceholder')" />
      <AppInput v-model="newSlot.description" :label="t('crm.caseDetail.docDescLabel')" :placeholder="t('crm.caseDetail.docDescPlaceholder')" />
      <div class="flex items-center gap-2">
        <input type="checkbox" v-model="newSlot.is_required" id="slotReq" class="rounded" />
        <label for="slotReq" class="text-sm text-gray-700">{{ t('crm.caseDetail.requiredCheckbox') }}</label>
      </div>
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showAddSlot = false">{{ t('common.cancel') }}</AppButton>
        <AppButton :loading="newSlot.loading" @click="addSlot">{{ t('common.add') }}</AppButton>
      </div>
    </div>
  </AppModal>

  <AppModal v-model="showRejectModal" :title="t('crm.caseDetail.rejectModalTitle')">
    <div class="space-y-4">
      <AppInput v-model="rejectNote" :label="t('crm.caseDetail.rejectCommentLabel')" :placeholder="t('crm.caseDetail.rejectCommentPlaceholder')" />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showRejectModal = false">{{ t('common.cancel') }}</AppButton>
        <AppButton variant="danger" @click="submitReject">{{ t('crm.caseDetail.rejectBtn') }}</AppButton>
      </div>
    </div>
  </AppModal>

  <AppModal v-model="showTranslationModal" :title="t('crm.caseDetail.translationModalTitle')">
    <div class="space-y-4">
      <p class="text-sm text-gray-600">{{ t('crm.caseDetail.translationDocLabel', { name: translationItem?.name }) }}</p>
      <AppInput v-model="translationForm.pages" type="number" :label="t('crm.caseDetail.translationPagesLabel')" :placeholder="t('crm.caseDetail.translationPagesPlaceholder')" />
      <AppInput v-model="translationForm.notes" :label="t('crm.caseDetail.translationCommentLabel')" :placeholder="t('crm.caseDetail.translationCommentPlaceholder')" />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showTranslationModal = false">{{ t('common.cancel') }}</AppButton>
        <AppButton @click="submitTranslation">{{ t('crm.caseDetail.sendToTranslation') }}</AppButton>
      </div>
    </div>
  </AppModal>

  <AppModal v-model="showResultModal" :title="t('crm.caseDetail.resultModalTitle')">
    <div class="space-y-4">
      <div class="flex gap-3">
        <button @click="resultForm.result_type = 'approved'"
          :class="['flex-1 py-3 rounded-xl border-2 text-center font-medium text-sm transition-colors', resultForm.result_type === 'approved' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 text-gray-400 hover:border-gray-300']">{{ t('crm.caseDetail.resultApproved') }}</button>
        <button @click="resultForm.result_type = 'rejected'"
          :class="['flex-1 py-3 rounded-xl border-2 text-center font-medium text-sm transition-colors', resultForm.result_type === 'rejected' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 text-gray-400 hover:border-gray-300']">{{ t('crm.caseDetail.resultRejected') }}</button>
      </div>
      <template v-if="resultForm.result_type === 'approved'">
        <AppInput v-model="resultForm.visa_issued_at" type="date" :label="t('crm.caseDetail.visaIssuedDate')" />
        <AppInput v-model="resultForm.visa_received_at" type="date" :label="t('crm.caseDetail.visaReceivedDate')" />
        <AppInput v-model="resultForm.visa_validity" :label="t('crm.caseDetail.visaValidityLabel')" :placeholder="t('crm.caseDetail.visaValidityPlaceholder')" />
      </template>
      <template v-if="resultForm.result_type === 'rejected'">
        <AppInput v-model="resultForm.rejection_reason" :label="t('crm.caseDetail.rejectionReasonLabel')" />
        <div class="flex items-center gap-2">
          <input type="checkbox" v-model="resultForm.can_reapply" id="canReapply" class="rounded" />
          <label for="canReapply" class="text-sm text-gray-700">{{ t('crm.caseDetail.canReapplyCheckbox') }}</label>
        </div>
        <AppInput v-if="resultForm.can_reapply" v-model="resultForm.reapply_recommendation" :label="t('crm.caseDetail.reapplyRecommendation')" />
      </template>
      <AppInput v-model="resultForm.result_notes" :label="t('crm.caseDetail.resultNotesLabel')" />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showResultModal = false">{{ t('common.cancel') }}</AppButton>
        <AppButton :loading="resultForm.loading" @click="doComplete" :disabled="!resultForm.result_type">{{ t('common.save') }}</AppButton>
      </div>
    </div>
  </AppModal>

  <AppModal v-model="showExpectedDateModal" :title="t('crm.caseDetail.expectedDateModalTitle')">
    <div class="space-y-4">
      <AppInput v-model="expectedDateForm.expected_result_date" type="date" :label="t('crm.caseDetail.newDateLabel')" />
      <AppInput v-model="expectedDateForm.notes" :label="t('crm.caseDetail.reasonLabel')" />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showExpectedDateModal = false">{{ t('common.cancel') }}</AppButton>
        <AppButton :loading="expectedDateForm.loading" @click="doUpdateExpectedDate">{{ t('common.save') }}</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- Assign manager modal -->
  <AppModal v-model="showAssignModal" :title="t('crm.caseDetail.assignModalTitle')">
    <div class="space-y-4">
      <div>
        <SearchSelect
          v-model="assignForm.manager_id"
          :items="managerItemsWithEmail"
          :label="t('crm.caseDetail.managerLabel')"
          :placeholder="t('crm.caseDetail.selectManager')"
          allow-all
          :all-label="t('crm.caseDetail.selectManager')"
        />
      </div>
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showAssignModal = false">{{ t('common.cancel') }}</AppButton>
        <AppButton :loading="assignForm.loading" :disabled="!assignForm.manager_id" @click="doAssign">{{ t('crm.caseDetail.assign') }}</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- File preview -->
  <div v-if="preview" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4" @click.self="preview = null">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
      <div class="flex items-center justify-between px-5 py-4 border-b shrink-0">
        <p class="font-medium text-gray-800 truncate max-w-[70%]">{{ preview.original_name }}</p>
        <div class="flex items-center gap-4">
          <a :href="preview.url" download class="text-sm text-blue-600 hover:underline">{{ t('crm.caseDetail.previewDownload') }}</a>
          <button @click="preview = null" class="text-gray-400 hover:text-gray-700 text-xl leading-none">x</button>
        </div>
      </div>
      <div class="flex-1 overflow-auto p-4 min-h-0">
        <img v-if="isImage(preview.mime_type)" :src="preview.url" class="max-w-full mx-auto rounded-lg shadow" />
        <iframe v-else-if="isPdf(preview.mime_type)" :src="preview.url" class="w-full rounded-lg border" style="height:70vh"></iframe>
        <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
          <p class="text-sm">{{ t('crm.caseDetail.previewUnavailable') }}</p>
          <a :href="preview.url" download class="mt-4 text-blue-600 text-sm hover:underline">{{ t('crm.caseDetail.previewDownloadFile') }}</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { casesApi } from '@/api/cases';
import { useCountries } from '@/composables/useCountries';
import AppBadge  from '@/components/AppBadge.vue';
import AppButton from '@/components/AppButton.vue';
import AppModal  from '@/components/AppModal.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppInput  from '@/components/AppInput.vue';
import DocItem   from '@/components/DocItem.vue';
import ReadinessPanel from '@/components/engine/ReadinessPanel.vue';
import CheckpointsList from '@/components/engine/CheckpointsList.vue';
import FormWizard from '@/components/engine/FormWizard.vue';
import CaseGuidancePanel from '@/components/engine/CaseGuidancePanel.vue';
import CaseAiDashboard from '@/components/CaseAiDashboard.vue';
import { useAuthStore } from '@/stores/auth';
import { usersApi } from '@/api/users';
import { formatPhone } from '@/utils/format';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();

const { countryName, countryFlag, visaTypeName } = useCountries();
const auth = useAuthStore();
const isOwner = computed(() => auth.isOwner);
const route  = useRoute();
const router = useRouter();
const id     = route.params.id;

// State
const caseData  = ref(null);
const checklist = ref({ items: [], progress: null });
const activeDocTab = ref('all');
const allowedTransitions = ref({});
const loading   = ref(true);
const toast     = ref({ msg: '', type: 'error' });
function showToast(msg, type = 'success') {
  toast.value = { msg, type };
  setTimeout(() => { toast.value = { msg: '', type: 'success' }; }, 4000);
}
const aiAnalyzingId = ref(null);
const portrait = ref(null);
const profile = ref(null);
const portraitLoading = ref(false);
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

// Engine refs
const readinessPanelRef = ref(null);
const checkpointsListRef = ref(null);
const formWizardRef = ref(null);
const guidancePanelRef = ref(null);
const aiDashboardRef = ref(null);
const hasEngine = computed(() => ['FR'].includes(caseData.value?.country_code)); // Countries with engine rules

function onEngineInit() {
  load(); // Reload case data to get new visa_case_rule_id
}
function onEngineUpdate() {
  readinessPanelRef.value?.refresh();
}

const managers = ref([]);
const managerItems = computed(() =>
  managers.value.map(m => ({ value: m.id, label: m.name }))
);
const managerItemsWithEmail = computed(() =>
  managers.value.map(m => ({ value: m.id, label: `${m.name} (${m.email})` }))
);
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
const STAGE_KEYS = ['lead', 'qualification', 'documents', 'doc_review', 'translation', 'ready', 'review', 'result'];
const STAGE_I18N = {
  lead: 'crm.caseDetail.stagesLead',
  qualification: 'crm.caseDetail.stagesQualification',
  documents: 'crm.caseDetail.stagesDocuments',
  doc_review: 'crm.caseDetail.stagesDocReview',
  translation: 'crm.caseDetail.stagesTranslation',
  ready: 'crm.caseDetail.stagesReady',
  review: 'crm.caseDetail.stagesReview',
  result: 'crm.caseDetail.stagesResult',
};
const STAGES = computed(() => STAGE_KEYS.map(key => ({ key, label: t(STAGE_I18N[key]) })));
const STAGE_LABELS = computed(() => Object.fromEntries(STAGES.value.map(s => [s.key, s.label])));
const STAGE_COLORS = {
  lead: 'gray', qualification: 'blue', documents: 'purple',
  doc_review: 'orange', translation: 'yellow', ready: 'blue', review: 'blue', result: 'green',
};
const stageOptions = computed(() => {
  const current = caseData.value?.stage;
  const allowed = allowedTransitions.value[current] || [];
  return STAGES.value.filter(s => allowed.includes(s.key)).map(s => ({ value: s.key, label: s.label }));
});

const STAGE_TASK_KEYS = {
  lead: ['taskLead1', 'taskLead2', 'taskLead3', 'taskLead4'],
  qualification: ['taskQual1', 'taskQual2', 'taskQual3', 'taskQual4', 'taskQual5', 'taskQual6'],
  documents: ['taskDocs1', 'taskDocs2', 'taskDocs3', 'taskDocs4'],
  doc_review: ['taskDocRev1', 'taskDocRev2', 'taskDocRev3', 'taskDocRev4'],
  translation: ['taskTrans1', 'taskTrans2', 'taskTrans3', 'taskTrans4'],
  ready: ['taskReady1', 'taskReady2', 'taskReady3', 'taskReady4'],
  review: ['taskReview1', 'taskReview2', 'taskReview3', 'taskReview4'],
  result: ['taskResult1', 'taskResult2', 'taskResult3', 'taskResult4'],
};
const STAGE_GOAL_KEYS = { lead: 'goalLead', qualification: 'goalQualification', documents: 'goalDocuments', doc_review: 'goalDocReview', translation: 'goalTranslation', ready: 'goalReady', review: 'goalReview', result: 'goalResult' };
const STAGE_RESULT_KEYS = { lead: 'resultLead', qualification: 'resultQualification', documents: 'resultDocuments', doc_review: 'resultDocReview', translation: 'resultTranslation', ready: 'resultReady', review: 'resultReview', result: 'resultResult' };
const STAGE_CONFIG = computed(() => {
  const cfg = {};
  for (const key of STAGE_KEYS) {
    cfg[key] = {
      manager_goal: t(`crm.caseDetail.${STAGE_GOAL_KEYS[key]}`),
      manager_tasks: (STAGE_TASK_KEYS[key] || []).map(k => t(`crm.caseDetail.${k}`)),
      manager_result: t(`crm.caseDetail.${STAGE_RESULT_KEYS[key]}`),
    };
  }
  return cfg;
});

// Computed
const flagEmoji = computed(() => countryFlag(caseData.value?.country_code ?? ''));
const stageLabel = computed(() => STAGE_LABELS.value[caseData.value?.stage] ?? '');
const stageColor = computed(() => STAGE_COLORS[caseData.value?.stage] ?? 'gray');
const stageIdx = computed(() => STAGES.value.findIndex(s => s.key === caseData.value?.stage));
const currentStageConfig = computed(() => STAGE_CONFIG.value[caseData.value?.stage]);

function canMoveTo(stageKey) {
  const current = caseData.value?.stage;
  if (!current || current === stageKey) return false;
  return (allowedTransitions.value[current] || []).includes(stageKey);
}

const priorityColorMap = { low: 'gray', normal: 'blue', high: 'orange', urgent: 'red' };
const priorityLabel = computed(() => {
  const p = caseData.value?.priority;
  if (!p) return '';
  return t(`crm.priority.${p}`);
});
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

const isActionItem = (i) => {
  if (i.status === 'uploaded') return true;
  if (i.status === 'rejected') return true;
  if (i.status === 'needs_translation') return true;
  if (i.status === 'translated') return true;
  if (!i.document && !i.is_checked && i.type !== 'checkbox') return true;
  return false;
};

const countUploaded = (items) => items.filter(i => i.document || i.is_checked).length;

const docTabs = computed(() => {
  const items = checklist.value.items ?? [];
  const applicantItems = items.filter(i => !i.family_member_id);
  const familyMap = new Map();

  for (const item of items) {
    if (!item.family_member_id) continue;
    if (!familyMap.has(item.family_member_id)) {
      familyMap.set(item.family_member_id, {
        key: item.family_member_id,
        label: item.family_member_name || t('crm.caseDetail.familyMember'),
        items: [],
      });
    }
    familyMap.get(item.family_member_id).items.push(item);
  }

  const tabs = [
    { key: 'all', label: t('common.all'), items, uploaded: countUploaded(items), total: items.length },
    { key: 'applicant', label: caseData.value?.client?.name || t('crm.caseDetail.applicantDocs'), items: applicantItems, uploaded: countUploaded(applicantItems), total: applicantItems.length },
  ];

  for (const fm of familyMap.values()) {
    tabs.push({ key: fm.key, label: fm.label, items: fm.items, uploaded: countUploaded(fm.items), total: fm.items.length });
  }

  return tabs;
});

const activeTabItems = computed(() => {
  const tab = docTabs.value.find(t => t.key === activeDocTab.value);
  return tab?.items ?? [];
});
const activeTabUploaded = computed(() => countUploaded(activeTabItems.value));
const activeTabAction = computed(() => activeTabItems.value.filter(isActionItem));
const activeTabDone = computed(() => {
  const ids = new Set(activeTabAction.value.map(d => d.id));
  return activeTabItems.value.filter(i => !ids.has(i.id));
});

// SLA
const slaInfo = computed(() => {
  const h = caseData.value?.stage_sla_hours_left;
  if (h == null) return null;
  if (caseData.value?.stage_sla_overdue || h < 0) return { display: Math.abs(h) + 'ч', subText: t('crm.caseDetail.slaOverdue'), bg: 'bg-red-50', label: 'text-red-400', value: 'text-red-600', sub: 'text-red-400' };
  if (h <= 2) return { display: h + 'ч', subText: t('crm.caseDetail.slaRemaining'), bg: 'bg-orange-50', label: 'text-orange-400', value: 'text-orange-600', sub: 'text-orange-400' };
  return { display: h + 'ч', subText: t('crm.caseDetail.slaRemaining'), bg: 'bg-blue-50', label: 'text-blue-400', value: 'text-blue-600', sub: 'text-blue-400' };
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
  if (s === 'paid') return t('crm.caseDetail.paymentPaid');
  if (s === 'pending') return t('crm.caseDetail.paymentPending');
  return t('crm.caseDetail.paymentUnpaid');
});

function stepClass(idx) {
  if (stageIdx.value > idx) return 'bg-blue-500 text-white';
  if (stageIdx.value === idx) return 'bg-blue-600 text-white ring-4 ring-blue-100';
  return 'bg-gray-200 text-gray-400';
}

// Client portrait computed
const clientAge = computed(() => {
  const dob = portrait.value?.date_of_birth;
  if (!dob) return null;
  const birth = new Date(dob);
  const now = new Date();
  let age = now.getFullYear() - birth.getFullYear();
  if (now.getMonth() < birth.getMonth() || (now.getMonth() === birth.getMonth() && now.getDate() < birth.getDate())) age--;
  return age > 0 ? age : null;
});

const passportWarning = computed(() => {
  const exp = portrait.value?.passport_expires_at;
  if (!exp) return null;
  const days = Math.floor((new Date(exp) - new Date()) / 86400000);
  if (days < 0) return { text: t('crm.caseDetail.portraitPassportExpired'), cls: 'text-red-600' };
  if (days <= 90) return { text: t('crm.caseDetail.portraitPassportExpiring'), cls: 'text-yellow-600' };
  return null;
});

const countryScore = computed(() => {
  if (!portrait.value?.scores?.length || !caseData.value?.country_code) return null;
  return portrait.value.scores.find(s => s.country_code === caseData.value.country_code) || null;
});

const MARITAL_MAP = { married: 'portraitMarried', single: 'portraitSingle', divorced: 'portraitDivorced', widowed: 'portraitWidowed' };
const maritalLabel = computed(() => {
  const k = MARITAL_MAP[profile.value?.marital_status];
  return k ? t(`crm.caseDetail.${k}`) : '---';
});

const EMPLOYMENT_MAP = { government: 'portraitGovernment', private: 'portraitPrivate', business_owner: 'portraitBusinessOwner', self_employed: 'portraitSelfEmployed', student: 'portraitStudent', retired: 'portraitRetired', unemployed: 'portraitUnemployed' };
const employmentLabel = computed(() => {
  const k = EMPLOYMENT_MAP[profile.value?.employment_type];
  return k ? t(`crm.caseDetail.${k}`) : profile.value?.employment_type ?? '---';
});
const employmentColor = computed(() => {
  const et = profile.value?.employment_type;
  if (et === 'government') return 'text-green-700';
  if (et === 'unemployed') return 'text-red-600';
  if (et === 'student') return 'text-blue-600';
  return 'text-gray-700';
});

const EDUCATION_MAP = { phd: 'portraitPhd', master: 'portraitMaster', bachelor: 'portraitBachelor', secondary: 'portraitSecondary', none: 'portraitNoEducation' };
const educationLabel = computed(() => {
  const k = EDUCATION_MAP[profile.value?.education_level];
  return k ? t(`crm.caseDetail.${k}`) : '---';
});

function scoreColor(s) { return s >= 80 ? 'text-green-600' : s >= 60 ? 'text-yellow-600' : 'text-red-600'; }
function scoreBarColor(s) { return s >= 80 ? 'bg-green-500' : s >= 60 ? 'bg-yellow-400' : 'bg-red-400'; }
function fmtMoney(v) { return Number(v).toLocaleString('en-US', { maximumFractionDigits: 0 }); }

// Helpers
function cleanPhone(p) {
  let raw = p ?? '';
  // Защита от PHP serialize обёртки: s:13:"+998971550027";
  const m = raw.match(/^s:\d+:"(.+)";?$/);
  if (m) raw = m[1];
  return raw.replace(/[^0-9]/g, '');
}
function fmtFull(d) { if (!d) return '---'; return new Date(d).toLocaleDateString('uz-UZ', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }); }
function fmtShort(d) { if (!d) return '---'; return new Date(d).toLocaleDateString('uz-UZ', { day: '2-digit', month: '2-digit', year: 'numeric' }); }
function isImage(m) { return m?.startsWith('image/'); }
function isPdf(m) { return m === 'application/pdf'; }

// Data
const loadError = ref(null);
async function load() {
  loading.value = true;
  loadError.value = null;
  try {
    const [c, cl] = await Promise.all([casesApi.get(id), casesApi.getChecklist(id)]);
    const resp = c.data.data;
    caseData.value = resp.case ?? resp;
    if (resp.allowed_transitions) allowedTransitions.value = resp.allowed_transitions;
    checklist.value = cl.data.data;
    // Client portrait
    if (resp.client_portrait) {
      portrait.value = resp.client_portrait;
      profile.value = resp.client_portrait.profile ?? null;
    }
  } catch (e) {
    loadError.value = e.response?.data?.message || t('crm.caseDetail.loadError');
  } finally { loading.value = false; }
}
async function reloadChecklist() { checklist.value = (await casesApi.getChecklist(id)).data.data; }
async function reloadAll() {
  const [c, cl] = await Promise.all([casesApi.get(id), casesApi.getChecklist(id)]);
  const resp = c.data.data;
  caseData.value = resp.case ?? resp;
  if (resp.allowed_transitions) allowedTransitions.value = resp.allowed_transitions;
  checklist.value = cl.data.data;
}

// Actions
async function quickMove(stage) { await casesApi.moveStage(id, { stage }); await reloadAll(); }

const MAX_FILE_SIZE_MB = 20;

async function uploadToSlot(item, event) {
  const file = event.target?.files?.[0];
  if (!file) return;

  if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
    showToast(t('crm.doc.fileTooLarge', { max: MAX_FILE_SIZE_MB }), 'error');
    event.target.value = '';
    return;
  }

  try {
    const fd = new FormData();
    fd.append('file', file);
    await casesApi.uploadToSlot(id, item.id, fd);
    await reloadAll();
  } catch (e) {
    showToast(e?.response?.data?.message ?? t('crm.doc.uploadFailed'), 'error');
  } finally {
    if (event.target) event.target.value = '';
  }
}

async function doAiAnalyze(item) {
  aiAnalyzingId.value = item.id;
  try {
    await casesApi.aiAnalyze(id, item.id);
    await reloadChecklist();
    aiDashboardRef.value?.refresh();
  } catch (e) {
    console.error('AI analyze error:', e);
  } finally {
    aiAnalyzingId.value = null;
  }
}

async function toggleCheck(item) { await casesApi.checkSlot(id, item.id, !item.is_checked); await reloadChecklist(); }

function openReject(item) { rejectItem.value = item; rejectNote.value = ''; showRejectModal.value = true; }
async function submitReject() { await casesApi.reviewSlot(id, rejectItem.value.id, { status: 'rejected', notes: rejectNote.value }); showRejectModal.value = false; rejectItem.value = null; rejectNote.value = ''; await reloadAll(); }
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

async function deleteSlot(item) { if (!confirm(t('crm.caseDetail.confirmDeleteSlot'))) return; await casesApi.deleteChecklistItem(id, item.id); await reloadChecklist(); }

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

async function confirmDelete() { if (!confirm(t('crm.caseDetail.confirmDeleteCase'))) return; await casesApi.remove(id); router.push({ name: 'cases' }); }

async function loadManagers() {
  if (!auth.isOwner) return;
  try {
    const { data } = await usersApi.list();
    const list = Array.isArray(data.data) ? data.data : (data.data?.data ?? []);
    managers.value = list.filter(u => u.role === 'manager' || u.role === 'owner');
  } catch { /* silent */ }
}

async function doAssign() {
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
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s, transform 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
</style>
