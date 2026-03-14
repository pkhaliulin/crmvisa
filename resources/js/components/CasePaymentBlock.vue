<template>
  <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <!-- Header (collapsible) -->
    <button @click="isOpen = !isOpen" class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
          <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <span class="text-sm font-bold text-gray-900">{{ t('crm.casePayment.title') }}</span>
        <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full', statusBadgeClass]">{{ statusLabel }}</span>
      </div>
      <svg :class="['w-4 h-4 text-gray-400 transition-transform duration-200', isOpen ? 'rotate-180' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <!-- Body -->
    <div v-show="isOpen" class="px-5 pb-5 space-y-4">

      <!-- Summary bar -->
      <div class="bg-gray-50 rounded-xl p-4 space-y-3">
        <div class="grid grid-cols-3 gap-3 text-center">
          <div>
            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ t('crm.casePayment.totalPrice') }}</p>
            <p class="text-sm font-bold text-gray-900 mt-0.5">{{ formatAmount(totalPrice) }}</p>
          </div>
          <div>
            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ t('crm.casePayment.paid') }}</p>
            <p class="text-sm font-bold text-green-600 mt-0.5">{{ formatAmount(paidAmount) }}</p>
          </div>
          <div>
            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ t('crm.casePayment.remaining') }}</p>
            <p class="text-sm font-bold mt-0.5" :class="remainingAmount > 0 ? 'text-orange-500' : 'text-gray-400'">{{ formatAmount(remainingAmount) }}</p>
          </div>
        </div>
        <!-- Progress bar -->
        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
          <div class="h-full bg-green-500 rounded-full transition-all duration-500" :style="{ width: progressPercent + '%' }"></div>
        </div>
      </div>

      <!-- Deadline warning -->
      <div v-if="deadlineWarning" :class="['flex items-center gap-2 text-xs font-medium px-3 py-2 rounded-lg', deadlineWarning.class]">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        {{ deadlineWarning.text }}
      </div>

      <!-- Inline settings: total_price + deadline -->
      <div class="space-y-3">
        <!-- Total price -->
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-gray-500">{{ t('crm.casePayment.totalPrice') }}</span>
          <div class="flex items-center gap-1.5">
            <template v-if="!priceSaved || editingPrice">
              <input v-model.number="settingsForm.total_price" type="number" min="0" step="1000" ref="priceInput"
                :placeholder="'0'" @keyup.enter="savePriceField"
                class="w-32 border border-gray-200 focus:border-[#1BA97F] rounded-lg px-2 py-1 text-sm text-right outline-none" />
              <button @click="savePriceField" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-green-50 text-[#1BA97F]" :title="t('crm.casePayment.totalPrice')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
              </button>
            </template>
            <template v-else>
              <span class="text-sm font-bold text-gray-900">{{ formatAmount(totalPrice) }}</span>
              <button @click="editingPrice = true; $nextTick(() => $refs.priceInput?.focus())" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-gray-100 text-gray-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              </button>
            </template>
          </div>
        </div>
        <!-- Deadline -->
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-gray-500">{{ t('crm.casePayment.deadline') }}</span>
          <div class="flex items-center gap-1.5">
            <template v-if="!deadlineSaved || editingDeadline">
              <input v-model="settingsForm.payment_deadline" type="date" ref="deadlineInput"
                @change="saveDeadlineField"
                class="w-36 border border-gray-200 focus:border-[#1BA97F] rounded-lg px-2 py-1 text-sm outline-none" />
            </template>
            <template v-else>
              <span class="text-sm text-gray-700">{{ settingsForm.payment_deadline }}</span>
              <button @click="editingDeadline = true; $nextTick(() => $refs.deadlineInput?.focus())" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-gray-100 text-gray-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              </button>
            </template>
          </div>
        </div>
        <!-- Quick deadline buttons -->
        <div class="flex gap-1.5">
          <button v-for="d in quickDeadlineDays" :key="d" @click="setQuickDeadline(d)"
            class="text-[10px] px-2 py-0.5 rounded-md border border-gray-200 text-gray-500 hover:bg-gray-100 transition-colors">
            +{{ d }} {{ t('crm.casePayment.days') }}
          </button>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="flex flex-wrap gap-2">
        <button @click="showPaymentModal = true"
          class="text-xs px-4 py-2 rounded-lg bg-[#1BA97F] text-white font-medium hover:bg-[#168c69] transition-colors flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          {{ t('crm.casePayment.recordPayment') }}
        </button>
        <button @click="printInvoice" :disabled="printingInvoice"
          class="text-xs px-4 py-2 rounded-lg border border-gray-200 text-gray-600 font-medium hover:bg-gray-50 transition-colors flex items-center gap-1.5 disabled:opacity-50">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
          </svg>
          {{ t('crm.casePayment.printInvoice') }}
        </button>
        <button @click="printContract"
          class="text-xs px-4 py-2 rounded-lg border border-gray-200 text-gray-600 font-medium hover:bg-gray-50 transition-colors flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          {{ t('crm.casePayment.printContract') }}
        </button>
      </div>

      <!-- Payment history -->
      <div v-if="payments.length">
        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">{{ t('crm.casePayment.history') }}</h4>
        <div class="space-y-2">
          <div v-for="p in payments" :key="p.id"
            class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5 group">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-gray-900">{{ formatAmount(p.amount) }}</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-200 text-gray-600 font-medium">{{ methodLabel(p.payment_method) }}</span>
              </div>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-[11px] text-gray-400">{{ formatDate(p.paid_at) }}</span>
                <span v-if="p.comment" class="text-[11px] text-gray-400 truncate max-w-[200px]">-- {{ p.comment }}</span>
              </div>
            </div>
            <button @click="deletePayment(p.id)" :disabled="deletingId === p.id"
              class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-all p-1 rounded disabled:opacity-50">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
      <div v-else class="text-xs text-gray-400 text-center py-3">{{ t('crm.casePayment.noPayments') }}</div>
    </div>

    <!-- ===== RECORD PAYMENT MODAL ===== -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="showPaymentModal = false"></div>
          <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-base font-bold text-[#0A1F44]">{{ t('crm.casePayment.recordPayment') }}</h3>

            <!-- Amount -->
            <div>
              <label class="text-xs font-medium text-gray-500 block mb-1">{{ t('crm.casePayment.amount') }} <span class="text-red-500">*</span></label>
              <input v-model.number="paymentForm.amount" type="number" min="1" step="1000"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" />
            </div>

            <!-- Payment method (SearchSelect) -->
            <div>
              <SearchSelect
                v-model="paymentForm.payment_method"
                :items="paymentMethodItems"
                :label="t('crm.casePayment.paymentMethod')"
                :placeholder="t('crm.casePayment.selectMethod')"
                required
              />
            </div>

            <!-- Date -->
            <div>
              <label class="text-xs font-medium text-gray-500 block mb-1">{{ t('crm.casePayment.paidAt') }}</label>
              <input v-model="paymentForm.paid_at" type="date"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" />
            </div>

            <!-- Comment -->
            <div>
              <label class="text-xs font-medium text-gray-500 block mb-1">{{ t('crm.casePayment.comment') }}</label>
              <input v-model="paymentForm.comment" type="text" maxlength="255"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
                :placeholder="t('crm.casePayment.commentPlaceholder')" />
            </div>

            <!-- Buttons -->
            <div class="flex justify-end gap-2 pt-2">
              <button @click="showPaymentModal = false"
                class="text-xs px-4 py-2 rounded-lg border border-gray-200 text-gray-600 font-medium hover:bg-gray-50 transition-colors">
                {{ t('common.cancel') }}
              </button>
              <button @click="submitPayment" :disabled="submittingPayment || !paymentForm.amount || !paymentForm.payment_method"
                class="text-xs px-4 py-2 rounded-lg bg-[#1BA97F] text-white font-medium hover:bg-[#168c69] disabled:opacity-50 transition-colors">
                {{ submittingPayment ? t('common.loading') : t('common.save') }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();

const props = defineProps({
  caseId: { type: String, required: true },
  initialData: { type: Object, default: null },
});

// --- State ---
const isOpen = ref(true);
const payments = ref([]);
const loading = ref(false);
const showPaymentModal = ref(false);
const submittingPayment = ref(false);
const savingSettings = ref(false);
const deletingId = ref(null);
const printingInvoice = ref(false);

const quickDeadlineDays = [3, 7, 10];
const editingPrice = ref(false);
const editingDeadline = ref(false);
const priceSaved = ref(false);
const deadlineSaved = ref(false);

const settingsForm = ref({
  total_price: 0,
  payment_deadline: '',
});

const paymentForm = ref({
  amount: null,
  payment_method: '',
  paid_at: new Date().toISOString().slice(0, 10),
  comment: '',
});

// --- Payment method items ---
const paymentMethodItems = computed(() => [
  { value: 'cash', label: t('crm.casePayment.methodCash') },
  { value: 'terminal', label: t('crm.casePayment.methodTerminal') },
  { value: 'bank_transfer', label: t('crm.casePayment.methodBankTransfer') },
  { value: 'payme', label: t('crm.casePayment.methodPayme') },
  { value: 'click', label: t('crm.casePayment.methodClick') },
  { value: 'uzum', label: t('crm.casePayment.methodUzum') },
  { value: 'other', label: t('crm.casePayment.methodOther') },
]);

// --- Computed ---
const totalPrice = computed(() => settingsForm.value.total_price || 0);
const paidAmount = computed(() => payments.value.reduce((sum, p) => sum + (Number(p.amount) || 0), 0));
const remainingAmount = computed(() => Math.max(0, totalPrice.value - paidAmount.value));
const progressPercent = computed(() => {
  if (totalPrice.value <= 0) return 0;
  return Math.min(100, Math.round((paidAmount.value / totalPrice.value) * 100));
});

const paymentStatus = computed(() => {
  if (totalPrice.value <= 0) return 'none';
  if (paidAmount.value <= 0) return 'unpaid';
  if (paidAmount.value >= totalPrice.value) return 'paid';
  return 'partial';
});

const statusLabel = computed(() => {
  const map = {
    none: t('crm.casePayment.statusNone'),
    unpaid: t('crm.casePayment.statusUnpaid'),
    partial: t('crm.casePayment.statusPartial'),
    paid: t('crm.casePayment.statusPaid'),
  };
  return map[paymentStatus.value] || '';
});

const statusBadgeClass = computed(() => {
  const map = {
    none: 'bg-gray-100 text-gray-500',
    unpaid: 'bg-red-100 text-red-600',
    partial: 'bg-yellow-100 text-yellow-700',
    paid: 'bg-green-100 text-green-700',
  };
  return map[paymentStatus.value] || 'bg-gray-100 text-gray-500';
});

const deadlineWarning = computed(() => {
  if (!settingsForm.value.payment_deadline) return null;
  if (paymentStatus.value === 'paid') return null;

  const deadline = new Date(settingsForm.value.payment_deadline);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  deadline.setHours(0, 0, 0, 0);

  const diffDays = Math.round((deadline - today) / (1000 * 60 * 60 * 24));

  if (diffDays < 0) {
    return {
      text: t('crm.casePayment.deadlineOverdue', { days: Math.abs(diffDays) }),
      class: 'bg-red-50 text-red-600',
    };
  }
  if (diffDays <= 3) {
    return {
      text: t('crm.casePayment.deadlineNear', { days: diffDays }),
      class: 'bg-yellow-50 text-yellow-700',
    };
  }
  return null;
});

// --- Methods ---
function formatAmount(val) {
  if (!val && val !== 0) return '0';
  return Number(val).toLocaleString('ru-RU').replace(/,/g, ' ').replace(/\u00A0/g, ' ');
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function methodLabel(method) {
  const item = paymentMethodItems.value.find(i => i.value === method);
  return item ? item.label : method;
}

async function fetchPayments() {
  loading.value = true;
  try {
    const { data } = await api.get(`/cases/${props.caseId}/payments`);
    const result = data.data || data;
    // API возвращает: { total_price, paid_amount, remaining_balance, payment_status, payments: [...], payment_deadline, ... }
    if (result.payments && Array.isArray(result.payments)) {
      payments.value = result.payments;
    } else if (Array.isArray(result)) {
      payments.value = result;
    }
    if (result.total_price !== undefined) {
      settingsForm.value.total_price = result.total_price || 0;
      priceSaved.value = result.total_price > 0;
    }
    if (result.payment_deadline !== undefined) {
      settingsForm.value.payment_deadline = result.payment_deadline || '';
      deadlineSaved.value = !!result.payment_deadline;
    }
  } catch (e) {
    console.error('Failed to fetch payments:', e);
  } finally {
    loading.value = false;
  }
}

async function saveSettings() {
  savingSettings.value = true;
  try {
    const { data } = await api.patch(`/cases/${props.caseId}/payment-settings`, {
      total_price: settingsForm.value.total_price,
      payment_deadline: settingsForm.value.payment_deadline || null,
    });
    const result = data?.data || data;
    if (result?.total_price !== undefined) settingsForm.value.total_price = result.total_price;
    if (result?.payment_deadline !== undefined) settingsForm.value.payment_deadline = result.payment_deadline || '';
  } catch (e) {
    console.error('Failed to save settings:', e);
  } finally {
    savingSettings.value = false;
  }
}

async function savePriceField() {
  if (!settingsForm.value.total_price || settingsForm.value.total_price <= 0) return;
  await saveSettings();
  editingPrice.value = false;
  priceSaved.value = true;
}

async function saveDeadlineField() {
  if (!settingsForm.value.payment_deadline) return;
  await saveSettings();
  editingDeadline.value = false;
  deadlineSaved.value = true;
}

async function submitPayment() {
  submittingPayment.value = true;
  try {
    const { data } = await api.post(`/cases/${props.caseId}/payments`, {
      amount: paymentForm.value.amount,
      payment_method: paymentForm.value.payment_method,
      paid_at: paymentForm.value.paid_at,
      comment: paymentForm.value.comment || null,
    });
    const newPayment = data.data || data;
    payments.value.unshift(newPayment);
    showPaymentModal.value = false;
    resetPaymentForm();
  } catch (e) {
    console.error('Failed to submit payment:', e);
  } finally {
    submittingPayment.value = false;
  }
}

async function deletePayment(id) {
  deletingId.value = id;
  try {
    await api.delete(`/cases/${props.caseId}/payments/${id}`);
    payments.value = payments.value.filter(p => p.id !== id);
  } catch (e) {
    console.error('Failed to delete payment:', e);
  } finally {
    deletingId.value = null;
  }
}

function resetPaymentForm() {
  paymentForm.value = {
    amount: null,
    payment_method: '',
    paid_at: new Date().toISOString().slice(0, 10),
    comment: '',
  };
}

function setQuickDeadline(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  settingsForm.value.payment_deadline = d.toISOString().slice(0, 10);
  saveSettings();
}

async function printInvoice() {
  printingInvoice.value = true;
  try {
    const { data } = await api.get(`/cases/${props.caseId}/invoice`);
    const invoiceHtml = data.html || data;

    if (typeof invoiceHtml === 'string' && invoiceHtml.trim().startsWith('<')) {
      openPrintWindow(invoiceHtml);
    } else {
      // Fallback: generate simple invoice locally
      openPrintWindow(generateLocalInvoice());
    }
  } catch (e) {
    // If API not available, generate locally
    openPrintWindow(generateLocalInvoice());
  } finally {
    printingInvoice.value = false;
  }
}

function generateLocalInvoice() {
  const rows = payments.value.map(p => `
    <tr>
      <td style="padding:8px 12px;border-bottom:1px solid #eee">${formatDate(p.paid_at)}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #eee">${formatAmount(p.amount)}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #eee">${methodLabel(p.payment_method)}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #eee">${p.comment || ''}</td>
    </tr>
  `).join('');

  return `<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>${t('crm.casePayment.invoiceTitle')}</title>
<style>
  body{font-family:Arial,sans-serif;margin:40px;color:#0A1F44}
  h1{font-size:20px;margin-bottom:24px}
  table{width:100%;border-collapse:collapse;margin-top:16px}
  th{text-align:left;padding:8px 12px;border-bottom:2px solid #ddd;font-size:12px;color:#666;text-transform:uppercase}
  .summary{display:flex;gap:40px;margin-bottom:24px}
  .summary-item{text-align:center}
  .summary-label{font-size:11px;color:#888;text-transform:uppercase;margin-bottom:4px}
  .summary-value{font-size:18px;font-weight:bold}
  .green{color:#1BA97F}
  .orange{color:#E67E22}
  .footer{margin-top:40px;font-size:11px;color:#aaa;border-top:1px solid #eee;padding-top:16px}
</style></head><body>
<h1>${t('crm.casePayment.invoiceTitle')}</h1>
<div class="summary">
  <div class="summary-item">
    <div class="summary-label">${t('crm.casePayment.totalPrice')}</div>
    <div class="summary-value">${formatAmount(totalPrice.value)}</div>
  </div>
  <div class="summary-item">
    <div class="summary-label">${t('crm.casePayment.paid')}</div>
    <div class="summary-value green">${formatAmount(paidAmount.value)}</div>
  </div>
  <div class="summary-item">
    <div class="summary-label">${t('crm.casePayment.remaining')}</div>
    <div class="summary-value orange">${formatAmount(remainingAmount.value)}</div>
  </div>
</div>
${payments.value.length ? `
<table>
  <thead><tr>
    <th>${t('crm.casePayment.paidAt')}</th>
    <th>${t('crm.casePayment.amount')}</th>
    <th>${t('crm.casePayment.paymentMethod')}</th>
    <th>${t('crm.casePayment.comment')}</th>
  </tr></thead>
  <tbody>${rows}</tbody>
</table>` : `<p style="color:#aaa;text-align:center;margin-top:32px">${t('crm.casePayment.noPayments')}</p>`}
<div class="footer">VisaCRM -- ${new Date().toLocaleDateString('ru-RU')}</div>
</body></html>`;
}

function openPrintWindow(html) {
  const win = window.open('', '_blank', 'width=800,height=600');
  if (!win) return;
  win.document.write(html);
  win.document.close();
  win.onload = () => win.print();
}

async function printContract() {
  try {
    // Принять договор (сгенерировать номер)
    await api.post(`/cases/${props.caseId}/contract/accept`);
    // Получить данные договора
    const { data } = await api.get(`/cases/${props.caseId}/contract`);
    const c = data.data || data;
    const fmt = (v) => v ? Number(v).toLocaleString('ru-RU') : '0';
    const policy = (c.cancellation_policy || []).map(p =>
      `<tr><td style="padding:6px 10px;border:1px solid #ddd">${p.stage}</td><td style="padding:6px 10px;border:1px solid #ddd">${p.refund}</td><td style="padding:6px 10px;border:1px solid #ddd">${p.description}</td></tr>`
    ).join('');

    const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${t('crm.casePayment.contractTitle')}</title>
<style>body{font-family:Arial,sans-serif;margin:40px;color:#0A1F44;font-size:13px;line-height:1.6}
h1{font-size:18px;text-align:center;margin-bottom:8px}
h2{font-size:14px;margin-top:24px;border-bottom:1px solid #ccc;padding-bottom:4px}
.header{text-align:center;margin-bottom:24px}
.num{font-size:12px;color:#666}
.parties{display:flex;justify-content:space-between;gap:40px;margin:16px 0}
.party{flex:1}
.party h3{font-size:13px;color:#666;margin-bottom:4px}
table{width:100%;border-collapse:collapse;margin:8px 0}
th{text-align:left;padding:6px 10px;border:1px solid #ddd;background:#f5f5f5;font-size:11px}
td{padding:6px 10px;border:1px solid #ddd}
.amount{font-size:16px;font-weight:bold}
.signatures{display:flex;justify-content:space-between;margin-top:60px}
.sig{width:45%;border-top:1px solid #333;padding-top:8px;font-size:12px}
.footer{margin-top:40px;font-size:10px;color:#aaa;text-align:center}
@media print{body{margin:20px}}</style></head><body>
<div class="header">
<h1>${t('crm.casePayment.contractTitle')}</h1>
<p class="num">${t('crm.casePayment.contractNumber')}: ${c.contract_number || '—'}</p>
<p class="num">${t('crm.casePayment.contractDate')}: ${c.date || '—'}</p>
</div>
<div class="parties">
<div class="party"><h3>${t('crm.casePayment.contractAgency')}</h3>
<p><strong>${c.agency?.name || ''}</strong></p>
<p>${c.agency?.address || ''}</p>
<p>${c.agency?.phone || ''}</p></div>
<div class="party"><h3>${t('crm.casePayment.contractClient')}</h3>
<p><strong>${c.client?.name || ''}</strong></p>
<p>${c.client?.phone || ''}</p></div>
</div>
<h2>${t('crm.casePayment.contractService')}</h2>
<p>${c.service?.description || ''} (${c.case_number || ''})</p>
<h2>${t('crm.casePayment.contractPayment')}</h2>
<table>
<tr><td>${t('crm.casePayment.totalPrice')}</td><td class="amount">${fmt(c.payment?.total_price)} ${c.payment?.currency || 'UZS'}</td></tr>
<tr><td>${t('crm.casePayment.contractPrepayment')}</td><td>${fmt(c.payment?.prepayment)} ${c.payment?.currency || 'UZS'}</td></tr>
<tr><td>${t('crm.casePayment.remaining')}</td><td>${fmt(c.payment?.remaining)} ${c.payment?.currency || 'UZS'}</td></tr>
${c.payment?.deadline ? `<tr><td>${t('crm.casePayment.deadline')}</td><td>${c.payment.deadline}</td></tr>` : ''}
</table>
<h2>${t('crm.casePayment.contractCancellation')}</h2>
<table><thead><tr><th>${t('crm.casePayment.contractStage')}</th><th>${t('crm.casePayment.contractRefund')}</th><th>${t('crm.casePayment.contractCondition')}</th></tr></thead>
<tbody>${policy}</tbody></table>
<div class="signatures">
<div class="sig">${t('crm.casePayment.contractAgency')}: ________________</div>
<div class="sig">${t('crm.casePayment.contractClient')}: ________________</div>
</div>
<div class="footer">VisaCRM -- ${new Date().toLocaleDateString('ru-RU')}</div>
</body></html>`;

    openPrintWindow(html);
  } catch (e) {
    console.error('Contract error:', e);
  }
}

// --- Init ---
onMounted(() => {
  if (props.initialData) {
    settingsForm.value.total_price = props.initialData.total_price || 0;
    settingsForm.value.payment_deadline = props.initialData.payment_deadline || '';
    if (props.initialData.payments) {
      payments.value = props.initialData.payments;
    }
  }
  fetchPayments();
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
