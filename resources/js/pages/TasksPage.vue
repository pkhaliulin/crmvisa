<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <h2 class="text-lg font-semibold text-gray-800">{{ t('crm.tasks.title') }}</h2>
        <div v-if="counters" class="flex items-center gap-2">
          <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">
            {{ t('crm.tasks.counters.active') }}: {{ counters.total_active }}
          </span>
          <span v-if="counters.due_today > 0" class="text-xs px-2 py-0.5 rounded-full bg-yellow-50 text-yellow-700">
            {{ t('crm.tasks.dueToday') }}: {{ counters.due_today }}
          </span>
          <span v-if="counters.overdue > 0" class="text-xs px-2 py-0.5 rounded-full bg-red-50 text-red-600">
            {{ t('crm.tasks.overdue') }}: {{ counters.overdue }}
          </span>
          <span v-if="counters.awaiting_verification > 0" class="text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-600">
            {{ t('crm.tasks.filters.awaiting_verification') }}: {{ counters.awaiting_verification }}
          </span>
        </div>
      </div>
      <AppButton @click="openCreate">{{ t('crm.tasks.newTask') }}</AppButton>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-2">
      <button v-for="f in filterOptions" :key="f.key"
        class="px-3 py-1.5 text-xs rounded-lg border transition-all"
        :class="activeFilter === f.key
          ? 'bg-[#0A1F44] text-white border-[#0A1F44]'
          : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
        @click="activeFilter = f.key">
        {{ f.label }}
        <span v-if="f.count" class="ml-1 opacity-70">{{ f.count }}</span>
      </button>

      <div class="ml-auto flex items-center gap-2">
        <select v-if="users.length" v-model="filterAssignee"
          class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white">
          <option value="">{{ t('crm.tasks.assignee') }}</option>
          <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
        <input v-model="searchQuery"
          :placeholder="t('crm.tasks.search')"
          class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 w-48 focus:outline-none focus:border-blue-300" />
      </div>
    </div>

    <!-- Quick add -->
    <div class="bg-white rounded-xl border border-dashed border-gray-300 p-3 hover:border-blue-300 transition-colors">
      <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <input v-model="quickTitle"
          :placeholder="t('crm.tasks.taskTitlePlaceholder')"
          class="flex-1 text-sm text-gray-700 bg-transparent outline-none"
          @keydown.enter="quickAdd" />
        <select v-model="quickPriority" class="text-xs border-0 bg-transparent text-gray-500 outline-none cursor-pointer">
          <option v-for="(label, key) in priorityLabels" :key="key" :value="key">{{ label }}</option>
        </select>
        <button v-if="quickTitle.trim()" @click="quickAdd"
          class="text-xs px-3 py-1 bg-[#0A1F44] text-white rounded-lg hover:bg-[#0e2a5c] transition-colors">
          Enter
        </button>
      </div>
    </div>

    <!-- Task list -->
    <div v-if="loading" class="text-center py-12 text-gray-400 text-sm">...</div>

    <div v-else-if="tasks.length === 0" class="text-center py-12">
      <p class="text-gray-400 text-sm">{{ t('crm.tasks.emptyFiltered') }}</p>
    </div>

    <div v-else class="space-y-1">
      <div v-for="task in tasks" :key="task.id"
        class="group bg-white rounded-xl border border-gray-200 px-4 py-3 hover:border-blue-200 hover:shadow-sm transition-all"
        :class="{
          'opacity-50': isTerminal(task),
          'border-l-4 border-l-red-400': !isTerminal(task) && isOverdue(task),
          'border-l-4 border-l-yellow-400': !isTerminal(task) && isDueToday(task) && !isOverdue(task),
          'border-l-4 border-l-purple-400': task.status === 'completed',
        }">
        <div class="flex items-start gap-3">
          <!-- Status transition button -->
          <button @click="transitionTask(task)" class="mt-0.5 shrink-0" :title="nextStatusLabel(task)">
            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all"
              :class="statusCircleClass(task)">
              <svg v-if="task.status === 'closed'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
              </svg>
              <svg v-else-if="task.status === 'verified'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
              </svg>
              <svg v-else-if="task.status === 'completed'" class="w-2.5 h-2.5 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="6"/>
              </svg>
              <svg v-else-if="task.status === 'accepted'" class="w-2.5 h-2.5 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="4"/>
              </svg>
            </div>
          </button>

          <!-- Content -->
          <div class="flex-1 min-w-0 cursor-pointer" @click="openEdit(task)">
            <p class="text-sm font-medium text-gray-800 leading-snug"
              :class="{ 'line-through text-gray-400': isTerminal(task) }">
              {{ task.title }}
            </p>
            <div class="flex flex-wrap items-center gap-2 mt-1">
              <!-- Status badge -->
              <span class="text-[10px] px-1.5 py-0.5 rounded font-medium" :class="statusBadgeClass(task.status)">
                {{ statusLabels[task.status] }}
              </span>
              <!-- Priority badge -->
              <span class="text-[10px] px-1.5 py-0.5 rounded font-medium" :class="priorityClass(task.priority)">
                {{ priorityLabels[task.priority] }}
              </span>
              <!-- Due date -->
              <span v-if="task.due_date" class="text-[10px] flex items-center gap-1" :class="dueDateClass(task)">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
                {{ formatDueDate(task.due_date) }}
              </span>
              <!-- Recurrence -->
              <span v-if="task.recurrence_rule" class="text-[10px] text-indigo-500 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M21.015 4.356v4.992"/>
                </svg>
                {{ recurrenceLabels[task.recurrence_rule] }}
              </span>
              <!-- Assignee -->
              <span v-if="task.assignee" class="text-[10px] text-gray-400 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/>
                </svg>
                {{ task.assignee.name }}
              </span>
              <!-- Creator (if different from assignee) -->
              <span v-if="task.creator && task.creator.id !== task.assigned_to" class="text-[10px] text-gray-300">
                {{ t('crm.tasks.creator') }}: {{ task.creator.name }}
              </span>
              <!-- Linked case -->
              <span v-if="task.visa_case" class="text-[10px] text-blue-500 cursor-pointer hover:underline"
                @click.stop="router.push({ name: 'cases.show', params: { id: task.visa_case.id } })">
                #{{ task.visa_case.case_number }}
              </span>
            </div>
          </div>

          <!-- Inline actions -->
          <div class="shrink-0 flex items-center gap-1">
            <!-- Defer -->
            <button v-if="!isTerminal(task) && task.status !== 'deferred' && task.status !== 'completed'"
              @click="setStatus(task, 'deferred')"
              class="p-1 text-gray-300 hover:text-yellow-500 opacity-0 group-hover:opacity-100 transition-all"
              :title="t('crm.tasks.defer')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </button>
            <!-- Reopen from deferred -->
            <button v-if="task.status === 'deferred'"
              @click="setStatus(task, 'new')"
              class="p-1 text-yellow-500 hover:text-blue-500"
              :title="t('crm.tasks.reopen')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
              </svg>
            </button>
            <!-- Edit -->
            <button @click="openEdit(task)"
              class="p-1 text-gray-300 hover:text-blue-500 opacity-0 group-hover:opacity-100 transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
              </svg>
            </button>
            <!-- Delete -->
            <button @click="deleteTask(task)"
              class="p-1 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Task modal -->
    <AppModal :show="showModal" @close="showModal = false" :title="editingTask ? t('crm.tasks.editTask') : t('crm.tasks.newTask')">
      <form @submit.prevent="saveTask" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.tasks.taskTitle') }} *</label>
          <input v-model="form.title" required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.tasks.description') }}</label>
          <textarea v-model="form.description" rows="3"
            :placeholder="t('crm.tasks.descriptionPlaceholder')"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.tasks.priority') }}</label>
            <select v-model="form.priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option v-for="(label, key) in priorityLabels" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.tasks.dueDate') }}</label>
            <input v-model="form.due_date" type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.tasks.assignee') }}</label>
            <select v-model="form.assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option value="">{{ t('crm.tasks.unassigned') }}</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.tasks.recurrence.none').split(' ')[0] }}</label>
            <select v-model="form.recurrence_rule" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option value="">{{ t('crm.tasks.recurrence.none') }}</option>
              <option v-for="(label, key) in recurrenceLabels" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
        </div>
        <div v-if="editingTask" class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.tasks.status') }}</label>
            <select v-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="showModal = false"
            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">{{ t('crm.tasks.cancel') }}</button>
          <AppButton type="submit" :loading="saving">
            {{ editingTask ? t('crm.tasks.updated') : t('crm.tasks.created') }}
          </AppButton>
        </div>
      </form>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { tasksApi } from '@/api/tasks';
import { usersApi } from '@/api/users';
import AppButton from '@/components/AppButton.vue';
import AppModal from '@/components/AppModal.vue';

const { t } = useI18n();
const router = useRouter();

const tasks = ref([]);
const users = ref([]);
const counters = ref(null);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingTask = ref(null);
const activeFilter = ref('active');
const filterAssignee = ref('');
const searchQuery = ref('');
const quickTitle = ref('');
const quickPriority = ref('medium');

const form = ref({
  title: '', description: '', priority: 'medium', status: 'new',
  assigned_to: '', due_date: '', recurrence_rule: '',
});

const priorityLabels = computed(() => ({
  low: t('crm.tasks.priorities.low'),
  medium: t('crm.tasks.priorities.medium'),
  high: t('crm.tasks.priorities.high'),
  urgent: t('crm.tasks.priorities.urgent'),
}));

const statusLabels = computed(() => ({
  new: t('crm.tasks.statuses.new'),
  accepted: t('crm.tasks.statuses.accepted'),
  completed: t('crm.tasks.statuses.completed'),
  verified: t('crm.tasks.statuses.verified'),
  closed: t('crm.tasks.statuses.closed'),
  deferred: t('crm.tasks.statuses.deferred'),
  cancelled: t('crm.tasks.statuses.cancelled'),
}));

const recurrenceLabels = computed(() => ({
  daily: t('crm.tasks.recurrence.daily'),
  weekdays: t('crm.tasks.recurrence.weekdays'),
  weekly: t('crm.tasks.recurrence.weekly'),
  monthly: t('crm.tasks.recurrence.monthly'),
  mon: t('crm.tasks.recurrence.mon'),
  tue: t('crm.tasks.recurrence.tue'),
  wed: t('crm.tasks.recurrence.wed'),
  thu: t('crm.tasks.recurrence.thu'),
  fri: t('crm.tasks.recurrence.fri'),
}));

const filterOptions = computed(() => [
  { key: 'active', label: t('crm.tasks.filters.active') },
  { key: 'all', label: t('crm.tasks.filters.all') },
  { key: 'my', label: t('crm.tasks.filters.my'), count: counters.value?.my_tasks },
  { key: 'created_by_me', label: t('crm.tasks.filters.created_by_me'), count: counters.value?.created_by_me },
  { key: 'today', label: t('crm.tasks.filters.today'), count: counters.value?.due_today },
  { key: 'overdue', label: t('crm.tasks.filters.overdue'), count: counters.value?.overdue },
  { key: 'awaiting_verification', label: t('crm.tasks.filters.awaiting_verification'), count: counters.value?.awaiting_verification },
  { key: 'completed', label: t('crm.tasks.filters.completed') },
  { key: 'recurring', label: t('crm.tasks.filters.recurring') },
]);

// API
async function fetchTasks() {
  loading.value = true;
  try {
    const params = {};
    const map = {
      active: () => {},  // default server behavior
      all: () => {},
      my: () => { params.assigned_to = 'me'; },
      created_by_me: () => { params.created_by = 'me'; },
      today: () => { params.due_today = 1; },
      overdue: () => { params.overdue = 1; },
      awaiting_verification: () => { params.awaiting_verification = 1; },
      completed: () => { params.status = 'closed'; },
      recurring: () => { params.recurring = 1; },
    };
    (map[activeFilter.value] || map.active)();
    if (activeFilter.value === 'active') params.status = 'new';

    if (filterAssignee.value) params.assigned_to = filterAssignee.value;
    if (searchQuery.value) params.search = searchQuery.value;

    const [tasksRes, countersRes] = await Promise.all([
      tasksApi.list(params),
      tasksApi.counters(),
    ]);
    tasks.value = tasksRes.data.data?.data ?? tasksRes.data.data ?? [];
    counters.value = countersRes.data.data;
  } finally {
    loading.value = false;
  }
}

async function fetchUsers() {
  try {
    const res = await usersApi.list();
    users.value = res.data.data ?? [];
  } catch { /* ignore */ }
}

async function transitionTask(task) {
  if (isTerminal(task)) return;
  try {
    const res = await tasksApi.transition(task.id);
    Object.assign(task, res.data.data);
    tasksApi.counters().then(r => counters.value = r.data.data);
  } catch { /* ignore */ }
}

async function setStatus(task, status) {
  try {
    const res = await tasksApi.setStatus(task.id, { status });
    Object.assign(task, res.data.data);
    tasksApi.counters().then(r => counters.value = r.data.data);
  } catch { /* ignore */ }
}

async function quickAdd() {
  if (!quickTitle.value.trim()) return;
  saving.value = true;
  try {
    await tasksApi.create({ title: quickTitle.value.trim(), priority: quickPriority.value });
    quickTitle.value = '';
    quickPriority.value = 'medium';
    fetchTasks();
  } finally { saving.value = false; }
}

function openCreate() {
  editingTask.value = null;
  form.value = { title: '', description: '', priority: 'medium', status: 'new', assigned_to: '', due_date: '', recurrence_rule: '' };
  showModal.value = true;
}

function openEdit(task) {
  editingTask.value = task;
  form.value = {
    title: task.title,
    description: task.description || '',
    priority: task.priority,
    status: task.status,
    assigned_to: task.assigned_to || '',
    due_date: task.due_date ? task.due_date.substring(0, 10) : '',
    recurrence_rule: task.recurrence_rule || '',
  };
  showModal.value = true;
}

async function saveTask() {
  saving.value = true;
  try {
    const data = { ...form.value };
    if (!data.assigned_to) data.assigned_to = null;
    if (!data.due_date) data.due_date = null;
    if (!data.recurrence_rule) data.recurrence_rule = null;

    if (editingTask.value) {
      await tasksApi.update(editingTask.value.id, data);
    } else {
      await tasksApi.create(data);
    }
    showModal.value = false;
    fetchTasks();
  } finally { saving.value = false; }
}

async function deleteTask(task) {
  if (!confirm(t('crm.tasks.deleteConfirm'))) return;
  try { await tasksApi.remove(task.id); fetchTasks(); } catch { /* ignore */ }
}

// Helpers
function isTerminal(task) { return ['closed', 'cancelled'].includes(task.status); }
function isOverdue(task) {
  if (!task.due_date || isTerminal(task)) return false;
  return new Date(task.due_date) < new Date(new Date().toDateString());
}
function isDueToday(task) {
  if (!task.due_date) return false;
  return new Date(task.due_date).toDateString() === new Date().toDateString();
}

function formatDueDate(date) {
  if (!date) return '';
  const d = new Date(date);
  const today = new Date(new Date().toDateString());
  const tomorrow = new Date(today); tomorrow.setDate(tomorrow.getDate() + 1);
  if (d.getTime() === today.getTime()) return t('crm.tasks.dueToday');
  if (d.getTime() === tomorrow.getTime()) return t('crm.tasks.dueTomorrow');
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
}

function dueDateClass(task) {
  if (isTerminal(task)) return 'text-gray-400';
  if (isOverdue(task)) return 'text-red-500 font-medium';
  if (isDueToday(task)) return 'text-orange-500';
  return 'text-gray-400';
}

function priorityClass(p) {
  return { urgent: 'bg-red-100 text-red-700', high: 'bg-orange-100 text-orange-700', medium: 'bg-blue-100 text-blue-700', low: 'bg-gray-100 text-gray-500' }[p] || 'bg-gray-100 text-gray-500';
}

function statusBadgeClass(s) {
  return {
    new: 'bg-gray-100 text-gray-600',
    accepted: 'bg-blue-100 text-blue-700',
    completed: 'bg-purple-100 text-purple-700',
    verified: 'bg-green-100 text-green-700',
    closed: 'bg-green-50 text-green-500',
    deferred: 'bg-yellow-100 text-yellow-700',
    cancelled: 'bg-red-50 text-red-400',
  }[s] || 'bg-gray-100 text-gray-500';
}

function statusCircleClass(task) {
  return {
    new: 'border-gray-300 hover:border-blue-400',
    accepted: 'border-blue-400 hover:border-blue-500',
    completed: 'border-purple-400 hover:border-purple-500',
    verified: 'bg-green-500 border-green-500',
    closed: 'bg-green-500 border-green-500',
    deferred: 'border-yellow-400 hover:border-yellow-500',
    cancelled: 'border-red-300',
  }[task.status] || 'border-gray-300';
}

function nextStatusLabel(task) {
  const map = { new: 'accepted', accepted: 'completed', completed: 'verified', verified: 'closed', deferred: 'accepted' };
  const next = map[task.status];
  return next ? `${t('crm.tasks.nextStep')}: ${statusLabels.value[next]}` : '';
}

// Watchers
let debounceTimer;
watch([activeFilter, filterAssignee], () => fetchTasks());
watch(searchQuery, () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(fetchTasks, 300); });

onMounted(() => { fetchTasks(); fetchUsers(); });
</script>
