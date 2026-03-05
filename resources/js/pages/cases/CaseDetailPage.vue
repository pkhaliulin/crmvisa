<template>
  <div v-if="loading" class="flex items-center justify-center py-32">
    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
  </div>

  <div v-else-if="caseData" class="space-y-6 max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <button @click="$router.back()"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors shrink-0 mr-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <span class="text-2xl">{{ flagEmoji }}</span>
            <h2 class="text-xl font-bold text-gray-900">{{ countryName(caseData.country_code) }} — {{ visaTypeName(caseData.visa_type) }}</h2>
            <AppBadge :color="urgencyColor">{{ urgencyLabel }}</AppBadge>
          </div>
          <p class="text-sm text-gray-500">
            Клиент: <strong>{{ caseData.client?.name }}</strong>
            <span v-if="caseData.client?.phone"> · {{ caseData.client.phone }}</span>
          </p>
        </div>
        <div class="flex gap-2">
          <AppButton variant="outline" size="sm" @click="showMoveModal = true">Сменить этап</AppButton>
          <AppButton variant="danger"  size="sm" @click="confirmDelete">Удалить</AppButton>
        </div>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-4 border-t">
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Этап</p>
          <AppBadge :color="stageColor" class="mt-1">{{ stageLabel }}</AppBadge>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Приоритет</p>
          <p class="text-sm font-medium mt-1">{{ priorityLabel }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Дедлайн SLA</p>
          <p :class="['text-sm font-medium mt-1', deadlineClass]">{{ caseData.critical_date ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Менеджер</p>
          <p class="text-sm font-medium mt-1">{{ caseData.assignee?.name ?? '—' }}</p>
        </div>
      </div>
    </div>

    <!-- Action panels based on stage -->

    <!-- Submit to Embassy panel (stage = ready) -->
    <div v-if="caseData.stage === 'ready'" class="bg-blue-50 rounded-xl border border-blue-200 p-6">
      <h3 class="font-semibold text-blue-800 mb-3">Подача в посольство</h3>
      <p class="text-sm text-blue-700 mb-4">Все документы готовы. Укажите дату подачи и ожидаемую дату результата.</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <AppInput v-model="embassyForm.submitted_at" type="date" label="Дата подачи" />
        <AppInput v-model="embassyForm.expected_result_date" type="date" label="Ожидаемая дата результата" />
      </div>
      <AppButton :loading="embassyForm.loading" @click="doSubmitToEmbassy">Отметить подачу</AppButton>
    </div>

    <!-- Awaiting result panel (stage = review) -->
    <div v-if="caseData.stage === 'review'" class="bg-yellow-50 rounded-xl border border-yellow-200 p-6">
      <h3 class="font-semibold text-yellow-800 mb-3">Ожидание результата</h3>
      <div class="flex items-center gap-4 text-sm text-yellow-700 mb-4">
        <span v-if="caseData.submitted_at">Подано: {{ formatDateShort(caseData.submitted_at) }}</span>
        <span v-if="caseData.expected_result_date">Ожидаемый результат: {{ formatDateShort(caseData.expected_result_date) }}</span>
      </div>
      <div class="flex gap-2">
        <AppButton @click="showResultModal = true">Записать результат</AppButton>
        <AppButton variant="outline" @click="showExpectedDateModal = true">Изменить дату</AppButton>
      </div>
    </div>

    <!-- Result panel (stage = result) -->
    <div v-if="caseData.stage === 'result'" class="rounded-xl border p-6" :class="caseData.result_type === 'approved' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
      <h3 class="font-semibold mb-2" :class="caseData.result_type === 'approved' ? 'text-green-800' : 'text-red-800'">
        {{ caseData.result_type === 'approved' ? 'Виза одобрена' : 'Виза отклонена' }}
      </h3>
      <div class="text-sm space-y-1" :class="caseData.result_type === 'approved' ? 'text-green-700' : 'text-red-700'">
        <p v-if="caseData.result_notes">{{ caseData.result_notes }}</p>
        <p v-if="caseData.visa_issued_at">Выдана: {{ formatDateShort(caseData.visa_issued_at) }}</p>
        <p v-if="caseData.visa_received_at">Получена: {{ formatDateShort(caseData.visa_received_at) }}</p>
        <p v-if="caseData.visa_validity">Срок действия: {{ caseData.visa_validity }}</p>
        <p v-if="caseData.rejection_reason">Причина отказа: {{ caseData.rejection_reason }}</p>
        <p v-if="caseData.can_reapply !== null">Повторная подача: {{ caseData.can_reapply ? 'Возможна' : 'Нет' }}</p>
        <p v-if="caseData.reapply_recommendation">Рекомендация: {{ caseData.reapply_recommendation }}</p>
      </div>
    </div>

    <!-- Documents checklist -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <!-- Panel header -->
      <div class="flex items-center justify-between mb-1">
        <div>
          <h3 class="font-semibold text-gray-800">Документы</h3>
          <p v-if="checklist.progress" class="text-xs text-gray-400 mt-0.5">
            {{ checklist.progress.uploaded }} из {{ checklist.progress.total }} загружено · {{ checklist.progress.percent }}%
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            v-if="uploadedCount > 0"
            @click="downloadZip"
            :disabled="zipLoading"
            class="flex items-center gap-1.5 text-xs text-gray-600 border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 disabled:opacity-50 transition-colors"
          >{{ zipLoading ? 'Подготовка...' : 'Скачать все (ZIP)' }}</button>
          <AppButton size="sm" variant="outline" @click="showAddSlot = true">+ Добавить</AppButton>
        </div>
      </div>

      <!-- Progress bar -->
      <div v-if="checklist.progress?.total > 0" class="w-full bg-gray-100 rounded-full h-1.5 mb-5 mt-3">
        <div
          class="h-1.5 rounded-full transition-all duration-500"
          :class="checklist.progress.percent === 100 ? 'bg-green-500' : 'bg-blue-500'"
          :style="{ width: checklist.progress.percent + '%' }"
        ></div>
      </div>

      <!-- Checklist items -->
      <div v-if="checklist.items?.length" class="space-y-3">
        <div
          v-for="item in checklist.items"
          :key="item.id"
          class="border rounded-xl overflow-hidden transition-colors"
          :class="itemBorderClass(item)"
        >
          <div class="flex items-start gap-3 p-4">
            <!-- Status icon -->
            <div class="shrink-0 mt-0.5 text-lg select-none">
              <span v-if="item.type === 'checkbox'">{{ item.is_checked ? '✅' : '⬜' }}</span>
              <span v-else>{{ statusIcon(item) }}</span>
            </div>

            <!-- Main content -->
            <div class="flex-1 min-w-0">
              <!-- Document name -->
              <div class="flex items-center gap-2 flex-wrap">
                <p class="text-sm font-semibold text-gray-900">{{ item.name }}</p>
                <span v-if="!item.is_required" class="text-xs text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded-md">опционально</span>
                <AppBadge :color="slotStatusColor(item)">{{ slotStatusLabel(item) }}</AppBadge>
              </div>

              <!-- Description -->
              <p v-if="item.description" class="text-xs text-gray-400 mt-1 leading-relaxed">{{ item.description }}</p>

              <!-- Uploaded file row -->
              <div v-if="item.document" class="mt-2 flex items-center gap-3 flex-wrap">
                <button
                  @click="openPreview(item.document)"
                  class="flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 font-medium"
                >
                  <span>{{ fileIcon(item.document.mime_type) }}</span>
                  <span class="truncate max-w-[220px]">{{ item.document.original_name }}</span>
                </button>
                <span class="text-gray-200 text-xs">|</span>
                <a :href="item.document.url" download class="text-xs text-gray-500 hover:text-gray-700">Скачать</a>
                <span class="text-gray-200 text-xs">|</span>
                <label class="text-xs text-gray-400 hover:text-gray-600 cursor-pointer">
                  Заменить
                  <input type="file" class="hidden" @change="uploadToSlot(item, $event)" />
                </label>
              </div>

              <!-- Reject note -->
              <p v-if="item.notes && item.status === 'rejected'" class="mt-2 text-xs text-red-600 bg-red-50 rounded-lg px-3 py-1.5">
                {{ item.notes }}
              </p>

              <!-- Review notes -->
              <p v-if="item.review_notes && item.status !== 'rejected'" class="mt-2 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-1.5">
                {{ item.review_notes }}
              </p>

              <!-- Translation info -->
              <div v-if="item.review_status === 'needs_translation'" class="mt-2 p-3 bg-purple-50 rounded-lg border border-purple-100">
                <div class="flex items-center gap-2 text-xs text-purple-700 mb-1">
                  <span>Перевод: {{ item.translation_pages ?? '?' }} стр.</span>
                  <span v-if="item.translation_price"> · {{ item.translation_price.toLocaleString() }} сум</span>
                  <AppBadge :color="translationStatusColor(item)" class="ml-1">{{ translationStatusLabel(item) }}</AppBadge>
                </div>

                <!-- Upload translation -->
                <div v-if="item.status === 'needs_translation'" class="mt-2">
                  <label class="cursor-pointer text-xs px-3 py-1.5 rounded-lg border border-purple-200 text-purple-700 bg-purple-100 hover:bg-purple-200 font-medium inline-block">
                    Загрузить перевод
                    <input type="file" class="hidden" @change="doUploadTranslation(item, $event)" />
                  </label>
                </div>

                <!-- Approve translation -->
                <div v-if="item.status === 'translated'" class="mt-2 flex items-center gap-2">
                  <span class="text-xs text-purple-600">Перевод загружен</span>
                  <button @click="doApproveTranslation(item)" class="text-xs px-3 py-1.5 rounded-lg bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 font-medium">Одобрить перевод</button>
                </div>

                <div v-if="item.status === 'translation_approved'" class="mt-1 text-xs text-green-600 font-medium">Перевод одобрен</div>
              </div>
            </div>

            <!-- Right-side actions -->
            <div class="shrink-0 flex items-center gap-2 flex-wrap justify-end">

              <!-- Checkbox toggle -->
              <template v-if="item.type === 'checkbox'">
                <button
                  @click="toggleCheck(item)"
                  class="text-xs px-3 py-1.5 rounded-lg border font-medium transition-colors"
                  :class="item.is_checked ? 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                >{{ item.is_checked ? 'Готово' : 'Отметить' }}</button>
              </template>

              <!-- Upload button (empty slot) -->
              <template v-else-if="!item.document && !['approved','needs_translation','translated','translation_approved'].includes(item.status)">
                <label class="cursor-pointer text-xs px-3 py-1.5 rounded-lg border border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100 font-medium">
                  Загрузить
                  <input type="file" class="hidden" @change="uploadToSlot(item, $event)" />
                </label>
              </template>

              <!-- Manager review buttons -->
              <template v-if="item.document && item.status === 'uploaded'">
                <button @click="reviewSlot(item, 'approved')" class="text-xs px-2.5 py-1.5 rounded-lg bg-green-50 text-green-700 border border-green-200 hover:bg-green-100">Принять</button>
                <button @click="openTranslation(item)" class="text-xs px-2.5 py-1.5 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100">На перевод</button>
                <button @click="openReject(item)" class="text-xs px-2.5 py-1.5 rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100">Отклонить</button>
              </template>

              <!-- +1 for repeatable -->
              <button
                v-if="item.is_repeatable"
                @click="repeatSlot(item)"
                class="text-xs text-gray-400 hover:text-blue-600 px-1.5 py-1 rounded border border-dashed border-gray-300 hover:border-blue-400"
                title="Добавить ещё одного ребёнка"
              >+1</button>

              <!-- Delete custom slot -->
              <button
                v-if="!item.requirement_id && !item.country_requirement_id"
                @click="deleteSlot(item)"
                class="text-gray-300 hover:text-red-400 text-sm px-1 transition-colors"
              >✕</button>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400 py-6 text-center">Чек-лист документов пуст</p>
    </div>

    <!-- Stage history -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h3 class="font-semibold text-gray-800 mb-4">История этапов</h3>
      <div class="space-y-3">
        <div v-for="h in caseData.stage_history" :key="h.id" class="flex items-start gap-3">
          <div class="w-2 h-2 rounded-full bg-blue-400 mt-1.5 shrink-0"></div>
          <div>
            <p class="text-sm font-medium">{{ STAGE_LABELS[h.stage] ?? h.stage }}</p>
            <p class="text-xs text-gray-400">{{ formatDate(h.entered_at) }} · {{ h.user?.name ?? 'Система' }}</p>
            <p v-if="h.notes" class="text-xs text-gray-600 mt-0.5">{{ h.notes }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Move stage modal -->
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

  <!-- Add custom slot modal -->
  <AppModal v-model="showAddSlot" title="Добавить документ">
    <div class="space-y-4">
      <AppInput v-model="newSlot.name" label="Название документа" placeholder="Напр: Справка из налоговой" />
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

  <!-- Reject note modal -->
  <AppModal v-model="showRejectModal" title="Причина отклонения">
    <div class="space-y-4">
      <AppInput v-model="rejectNote" label="Комментарий для клиента" placeholder="Что не так с документом..." />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showRejectModal = false">Отмена</AppButton>
        <AppButton variant="danger" @click="submitReject">Отклонить</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- Translation modal -->
  <AppModal v-model="showTranslationModal" title="Отправить на перевод">
    <div class="space-y-4">
      <p class="text-sm text-gray-600">Документ: <strong>{{ translationItem?.name }}</strong></p>
      <AppInput v-model="translationForm.pages" type="number" label="Кол-во страниц" placeholder="1" />
      <AppInput v-model="translationForm.notes" label="Комментарий" placeholder="Что перевести, особые требования..." />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showTranslationModal = false">Отмена</AppButton>
        <AppButton @click="submitTranslation">Отправить на перевод</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- Result modal -->
  <AppModal v-model="showResultModal" title="Записать результат">
    <div class="space-y-4">
      <div class="flex gap-3">
        <button
          @click="resultForm.result_type = 'approved'"
          class="flex-1 py-3 rounded-xl border-2 text-center font-medium text-sm transition-colors"
          :class="resultForm.result_type === 'approved' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
        >Виза одобрена</button>
        <button
          @click="resultForm.result_type = 'rejected'"
          class="flex-1 py-3 rounded-xl border-2 text-center font-medium text-sm transition-colors"
          :class="resultForm.result_type === 'rejected' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
        >Отказ</button>
      </div>

      <!-- Approved fields -->
      <template v-if="resultForm.result_type === 'approved'">
        <AppInput v-model="resultForm.visa_issued_at" type="date" label="Дата выдачи визы" />
        <AppInput v-model="resultForm.visa_received_at" type="date" label="Дата получения" />
        <AppInput v-model="resultForm.visa_validity" label="Срок действия" placeholder="90 дней / 1 год..." />
      </template>

      <!-- Rejected fields -->
      <template v-if="resultForm.result_type === 'rejected'">
        <AppInput v-model="resultForm.rejection_reason" label="Причина отказа" placeholder="Причина..." />
        <div class="flex items-center gap-2">
          <input type="checkbox" v-model="resultForm.can_reapply" id="canReapply" class="rounded" />
          <label for="canReapply" class="text-sm text-gray-700">Повторная подача возможна</label>
        </div>
        <AppInput v-if="resultForm.can_reapply" v-model="resultForm.reapply_recommendation" label="Рекомендация" placeholder="Что исправить..." />
      </template>

      <AppInput v-model="resultForm.result_notes" label="Примечание" placeholder="Доп. информация..." />

      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showResultModal = false">Отмена</AppButton>
        <AppButton :loading="resultForm.loading" @click="doComplete" :disabled="!resultForm.result_type">Сохранить</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- Expected date modal -->
  <AppModal v-model="showExpectedDateModal" title="Изменить ожидаемую дату">
    <div class="space-y-4">
      <AppInput v-model="expectedDateForm.expected_result_date" type="date" label="Ожидаемая дата результата" />
      <AppInput v-model="expectedDateForm.notes" label="Примечание" placeholder="Причина изменения..." />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showExpectedDateModal = false">Отмена</AppButton>
        <AppButton :loading="expectedDateForm.loading" @click="doUpdateExpectedDate">Сохранить</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- File preview overlay -->
  <div
    v-if="preview"
    class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4"
    @click.self="preview = null"
  >
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
      <div class="flex items-center justify-between px-5 py-4 border-b shrink-0">
        <p class="font-medium text-gray-800 truncate max-w-[70%]">{{ preview.original_name }}</p>
        <div class="flex items-center gap-4">
          <a :href="preview.url" download class="text-sm text-blue-600 hover:underline">Скачать</a>
          <button @click="preview = null" class="text-gray-400 hover:text-gray-700 text-xl leading-none">✕</button>
        </div>
      </div>
      <div class="flex-1 overflow-auto p-4 min-h-0">
        <img
          v-if="isImage(preview.mime_type)"
          :src="preview.url"
          class="max-w-full mx-auto rounded-lg shadow"
          alt="preview"
        />
        <iframe
          v-else-if="isPdf(preview.mime_type)"
          :src="preview.url"
          class="w-full rounded-lg border"
          style="height: 70vh"
        ></iframe>
        <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
          <span class="text-5xl mb-4">{{ fileIcon(preview.mime_type) }}</span>
          <p class="text-sm">Предпросмотр недоступен для этого формата</p>
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

const { countryName, countryFlag, visaTypeName } = useCountries();

const route  = useRoute();
const router = useRouter();
const id     = route.params.id;

const caseData        = ref(null);
const checklist       = ref({ items: [], progress: null });
const loading         = ref(true);
const showMoveModal   = ref(false);
const showAddSlot     = ref(false);
const showRejectModal = ref(false);
const showTranslationModal = ref(false);
const showResultModal      = ref(false);
const showExpectedDateModal = ref(false);
const rejectNote      = ref('');
const rejectItem      = ref(null);
const translationItem = ref(null);
const preview         = ref(null);
const zipLoading      = ref(false);
const moveForm        = reactive({ stage: '', notes: '', loading: false });
const newSlot         = reactive({ name: '', description: '', is_required: false, loading: false });
const translationForm = reactive({ pages: 1, notes: '' });
const embassyForm     = reactive({ submitted_at: '', expected_result_date: '', loading: false });
const resultForm      = reactive({
  result_type: '', result_notes: '',
  visa_issued_at: '', visa_received_at: '', visa_validity: '',
  rejection_reason: '', can_reapply: false, reapply_recommendation: '',
  loading: false,
});
const expectedDateForm = reactive({ expected_result_date: '', notes: '', loading: false });

const STAGE_LABELS = {
  lead: 'Лид', qualification: 'Квалификация', documents: 'Документы',
  doc_review: 'Проверка док.', translation: 'Перевод', ready: 'Готов к подаче',
  review: 'Рассмотрение', result: 'Результат',
};
const STAGE_COLORS = {
  lead: 'gray', qualification: 'blue', documents: 'purple',
  doc_review: 'orange', translation: 'yellow', ready: 'blue',
  review: 'blue', result: 'green',
};
const stageOptions  = Object.entries(STAGE_LABELS).map(([value, label]) => ({ value, label }));
const flagEmoji     = computed(() => countryFlag(caseData.value?.country_code ?? ''));
const stageLabel    = computed(() => STAGE_LABELS[caseData.value?.stage] ?? '');
const stageColor    = computed(() => STAGE_COLORS[caseData.value?.stage] ?? 'gray');
const urgencyColor  = computed(() => {
  const u = caseData.value?.urgency ?? '';
  return u === 'overdue' ? 'red' : u === 'critical' ? 'yellow' : 'gray';
});
const urgencyLabel  = computed(() => {
  const u = caseData.value?.urgency;
  return u === 'overdue' ? 'Просрочено' : u === 'critical' ? 'Горящая' : 'В норме';
});
const deadlineClass = computed(() => {
  const u = caseData.value?.urgency;
  return u === 'overdue' ? 'text-red-600' : u === 'critical' ? 'text-yellow-600' : 'text-gray-700';
});
const priorityMap   = { low: 'Низкий', normal: 'Обычный', high: 'Высокий', urgent: 'Срочный' };
const priorityLabel = computed(() => priorityMap[caseData.value?.priority] ?? '');
const uploadedCount = computed(() =>
  (checklist.value.items ?? []).filter(i => i.document || i.is_checked).length
);

// --- Helpers ---
function itemBorderClass(item) {
  if (item.status === 'approved' || item.status === 'translation_approved') return 'border-green-200 bg-green-50/40';
  if (item.status === 'rejected')                       return 'border-red-200 bg-red-50/30';
  if (item.status === 'needs_translation')               return 'border-purple-200 bg-purple-50/30';
  if (item.status === 'translated')                      return 'border-purple-200 bg-purple-50/20';
  if (item.document || item.is_checked)                  return 'border-blue-200';
  return 'border-gray-200';
}
function slotStatusColor(item) {
  const s = item.status;
  if (s === 'approved' || s === 'translation_approved')  return 'green';
  if (s === 'rejected')                                  return 'red';
  if (s === 'needs_translation')                         return 'purple';
  if (s === 'translated')                                return 'purple';
  if (s === 'uploaded' || item.is_checked)               return 'blue';
  return 'gray';
}
function slotStatusLabel(item) {
  const s = item.status;
  if (s === 'approved')                                  return 'Принято';
  if (s === 'rejected')                                  return 'Отклонено';
  if (s === 'needs_translation')                         return 'На перевод';
  if (s === 'translated')                                return 'Переведено';
  if (s === 'translation_approved')                      return 'Перевод одобрен';
  if (s === 'uploaded' || item.is_checked)               return 'На проверке';
  return item.is_required ? 'Ожидает' : 'Не загружен';
}
function statusIcon(item) {
  const s = item.status;
  if (s === 'approved' || s === 'translation_approved')  return '✅';
  if (s === 'rejected')                                  return '❌';
  if (s === 'needs_translation' || s === 'translated')   return '📝';
  if (item.document)                                     return '📎';
  return '📋';
}
function translationStatusColor(item) {
  if (item.status === 'translation_approved') return 'green';
  if (item.status === 'translated')           return 'blue';
  return 'yellow';
}
function translationStatusLabel(item) {
  if (item.status === 'translation_approved') return 'Одобрен';
  if (item.status === 'translated')           return 'Загружен';
  return 'Ожидает';
}
function fileIcon(mime) {
  if (!mime)                                            return '📄';
  if (mime.startsWith('image/'))                        return '🖼️';
  if (mime === 'application/pdf')                       return '📕';
  if (mime.includes('word'))                            return '📝';
  if (mime.includes('sheet') || mime.includes('excel')) return '📊';
  return '📄';
}
function isImage(mime) { return mime?.startsWith('image/'); }
function isPdf(mime)   { return mime === 'application/pdf'; }
function formatDate(d) {
  return new Date(d).toLocaleDateString('ru-RU', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}
function formatDateShort(d) {
  return new Date(d).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// --- Data ---
async function load() {
  loading.value = true;
  try {
    const [cRes, clRes] = await Promise.all([
      casesApi.get(id),
      casesApi.getChecklist(id),
    ]);
    caseData.value  = cRes.data.data;
    checklist.value = clRes.data.data;
  } finally {
    loading.value = false;
  }
}
async function reloadChecklist() {
  const { data } = await casesApi.getChecklist(id);
  checklist.value = data.data;
}
async function reloadAll() {
  const [cRes, clRes] = await Promise.all([
    casesApi.get(id),
    casesApi.getChecklist(id),
  ]);
  caseData.value  = cRes.data.data;
  checklist.value = clRes.data.data;
}

// --- Actions ---
async function uploadToSlot(item, event) {
  const file = event.target.files?.[0];
  if (!file) return;
  const form = new FormData();
  form.append('file', file);
  await casesApi.uploadToSlot(id, item.id, form);
  await reloadAll();
}

async function toggleCheck(item) {
  await casesApi.checkSlot(id, item.id, !item.is_checked);
  await reloadChecklist();
}

function openReject(item) {
  rejectItem.value = item;
  rejectNote.value = '';
  showRejectModal.value = true;
}
async function submitReject() {
  await casesApi.reviewSlot(id, rejectItem.value.id, { status: 'rejected', notes: rejectNote.value });
  showRejectModal.value = false;
  await reloadAll();
}
async function reviewSlot(item, status) {
  await casesApi.reviewSlot(id, item.id, { status });
  await reloadAll();
}

function openTranslation(item) {
  translationItem.value = item;
  translationForm.pages = 1;
  translationForm.notes = '';
  showTranslationModal.value = true;
}
async function submitTranslation() {
  await casesApi.reviewSlot(id, translationItem.value.id, {
    status: 'needs_translation',
    notes: translationForm.notes || null,
    translation_pages: parseInt(translationForm.pages) || 1,
  });
  showTranslationModal.value = false;
  await reloadAll();
}

async function doUploadTranslation(item, event) {
  const file = event.target.files?.[0];
  if (!file) return;
  const form = new FormData();
  form.append('file', file);
  await casesApi.uploadTranslation(id, item.id, form);
  await reloadAll();
}

async function doApproveTranslation(item) {
  await casesApi.approveTranslation(id, item.id);
  await reloadAll();
}

async function doSubmitToEmbassy() {
  if (!embassyForm.submitted_at || !embassyForm.expected_result_date) return;
  embassyForm.loading = true;
  try {
    await casesApi.submitToEmbassy(id, {
      submitted_at: embassyForm.submitted_at,
      expected_result_date: embassyForm.expected_result_date,
    });
    await reloadAll();
  } finally {
    embassyForm.loading = false;
  }
}

async function doComplete() {
  if (!resultForm.result_type) return;
  resultForm.loading = true;
  try {
    await casesApi.complete(id, {
      result_type: resultForm.result_type,
      result_notes: resultForm.result_notes || null,
      visa_issued_at: resultForm.visa_issued_at || null,
      visa_received_at: resultForm.visa_received_at || null,
      visa_validity: resultForm.visa_validity || null,
      rejection_reason: resultForm.rejection_reason || null,
      can_reapply: resultForm.can_reapply,
      reapply_recommendation: resultForm.reapply_recommendation || null,
    });
    showResultModal.value = false;
    await reloadAll();
  } finally {
    resultForm.loading = false;
  }
}

async function doUpdateExpectedDate() {
  if (!expectedDateForm.expected_result_date) return;
  expectedDateForm.loading = true;
  try {
    await casesApi.updateExpectedDate(id, {
      expected_result_date: expectedDateForm.expected_result_date,
      notes: expectedDateForm.notes || null,
    });
    showExpectedDateModal.value = false;
    await reloadAll();
  } finally {
    expectedDateForm.loading = false;
  }
}

async function repeatSlot(item) {
  await casesApi.addChecklistItem(id, { name: item.name, description: item.description, is_required: false });
  await reloadChecklist();
}

async function addSlot() {
  if (!newSlot.name) return;
  newSlot.loading = true;
  try {
    await casesApi.addChecklistItem(id, {
      name: newSlot.name, description: newSlot.description, is_required: newSlot.is_required,
    });
    showAddSlot.value = false;
    Object.assign(newSlot, { name: '', description: '', is_required: false });
    await reloadChecklist();
  } finally {
    newSlot.loading = false;
  }
}

async function deleteSlot(item) {
  if (!confirm('Удалить этот пункт чек-листа?')) return;
  await casesApi.deleteChecklistItem(id, item.id);
  await reloadChecklist();
}

async function downloadZip() {
  zipLoading.value = true;
  try {
    const response = await casesApi.downloadAllZip(id);
    const url  = URL.createObjectURL(new Blob([response.data], { type: 'application/zip' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `docs-case-${id.slice(0, 8)}.zip`;
    link.click();
    URL.revokeObjectURL(url);
  } finally {
    zipLoading.value = false;
  }
}

function openPreview(doc) { preview.value = doc; }

async function doMoveStage() {
  if (!moveForm.stage) return;
  moveForm.loading = true;
  try {
    await casesApi.moveStage(id, { stage: moveForm.stage, notes: moveForm.notes || null });
    showMoveModal.value = false;
    await load();
  } finally {
    moveForm.loading = false;
  }
}

async function confirmDelete() {
  if (!confirm('Удалить заявку? Это действие необратимо.')) return;
  await casesApi.remove(id);
  router.push({ name: 'cases' });
}

onMounted(load);
</script>
