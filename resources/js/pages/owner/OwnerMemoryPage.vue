<template>
  <div class="flex gap-6" style="min-height: calc(100vh - 120px);">
    <!-- Sidebar файлов -->
    <aside class="w-64 bg-white rounded-xl border border-gray-100 flex flex-col flex-shrink-0 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100">
        <h3 class="text-sm font-bold text-[#0A1F44]">{{ $t('memory.title') }}</h3>
        <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('memory.subtitle') }}</p>
      </div>

      <div v-if="loading" class="px-4 py-3 text-sm text-gray-400">{{ $t('common.loading') }}</div>

      <nav v-else class="flex-1 overflow-y-auto py-1">
        <button v-for="f in files" :key="f.filename"
          @click="activeFile = f.filename"
          class="w-full text-left px-4 py-2.5 text-sm transition-colors border-l-3"
          :class="activeFile === f.filename
            ? 'bg-blue-50 border-[#0A1F44] text-[#0A1F44] font-semibold'
            : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-800'">
          <div class="flex items-center gap-2">
            <span class="w-5 h-5 flex items-center justify-center rounded text-[10px] font-bold"
              :class="activeFile === f.filename ? 'bg-[#0A1F44] text-white' : 'bg-gray-100 text-gray-400'">
              {{ fileIcon(f.name) }}
            </span>
            <span class="truncate">{{ fileLabel(f.name) }}</span>
          </div>
          <div class="text-[10px] text-gray-400 mt-0.5 pl-7">{{ formatSize(f.size) }}</div>
        </button>

        <button @click="activeFile = '__all__'"
          class="w-full text-left px-4 py-2.5 text-sm transition-colors border-l-3 mt-1 border-t border-gray-100"
          :class="activeFile === '__all__'
            ? 'bg-blue-50 border-[#0A1F44] text-[#0A1F44] font-semibold'
            : 'border-transparent text-gray-600 hover:bg-gray-50'">
          <div class="flex items-center gap-2">
            <span class="w-5 h-5 flex items-center justify-center rounded text-[10px] font-bold"
              :class="activeFile === '__all__' ? 'bg-[#0A1F44] text-white' : 'bg-gray-100 text-gray-400'">*</span>
            <span>{{ $t('memory.allFiles') }}</span>
          </div>
        </button>
      </nav>

      <div class="px-4 py-2.5 border-t border-gray-100 text-[10px] text-gray-400">
        {{ files.length }} {{ $t('memory.filesCount') }}
      </div>
    </aside>

    <!-- Контент -->
    <div class="flex-1 min-w-0">
      <div v-if="loading" class="p-8 text-gray-400">{{ $t('common.loading') }}</div>

      <div v-else-if="activeFile === '__all__'" class="space-y-8">
        <div v-for="f in files" :key="f.filename">
          <div class="flex items-center gap-2 mb-3">
            <span class="text-xs font-bold text-white bg-[#0A1F44] px-2 py-0.5 rounded">{{ f.filename }}</span>
            <span class="text-[10px] text-gray-400">{{ formatSize(f.size) }}</span>
          </div>
          <div class="prose prose-sm max-w-none bg-white rounded-xl border border-gray-100 p-6 shadow-sm"
            v-html="renderMarkdown(f.content)"></div>
        </div>
      </div>

      <div v-else-if="currentFile">
        <div class="flex items-center gap-3 mb-4">
          <h2 class="text-lg font-bold text-[#0A1F44]">{{ fileLabel(currentFile.name) }}</h2>
          <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded">{{ currentFile.filename }}</span>
        </div>
        <div class="prose prose-sm max-w-none bg-white rounded-xl border border-gray-100 p-6 shadow-sm"
          v-html="renderMarkdown(currentFile.content)"></div>
      </div>

      <div v-else class="p-8 text-gray-400">{{ $t('memory.selectFile') }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { monitoringApi } from '@/api/monitoring';

const { t } = useI18n();

const files = ref([]);
const loading = ref(true);
const activeFile = ref('');
const updatedAt = ref('');

const currentFile = computed(() =>
  files.value.find(f => f.filename === activeFile.value) || null
);

const FILE_LABELS = {
  'MEMORY': 'Основная память',
  'architecture-rules': 'Правила архитектуры',
  'saas-architecture': 'SaaS стратегия',
  'saas-scaling-rules': 'SaaS масштабирование',
  'quality-checklist': 'Чеклист качества',
};

const FILE_ICONS = {
  'MEMORY': 'M',
  'architecture-rules': 'A',
  'saas-architecture': 'S',
  'saas-scaling-rules': 'R',
  'quality-checklist': 'Q',
};

function fileLabel(name) {
  return FILE_LABELS[name] || name;
}

function fileIcon(name) {
  return FILE_ICONS[name] || name.charAt(0).toUpperCase();
}

function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  return (bytes / 1024).toFixed(1) + ' KB';
}

function renderMarkdown(md) {
  if (!md) return '';
  let html = md;

  // Code blocks (```...```)
  html = html.replace(/```(\w*)\n([\s\S]*?)```/g, (_, lang, code) => {
    const escaped = escapeHtml(code.trimEnd());
    return `<pre class="code-block"><code>${escaped}</code></pre>`;
  });

  // Inline code
  html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

  // Tables
  html = html.replace(/^(\|.+\|)\n(\|[\s:|-]+\|)\n((?:\|.+\|\n?)*)/gm, (_, header, sep, body) => {
    const ths = header.split('|').filter(c => c.trim()).map(c => `<th>${c.trim()}</th>`).join('');
    const rows = body.trim().split('\n').map(row => {
      const tds = row.split('|').filter(c => c.trim()).map(c => `<td>${c.trim()}</td>`).join('');
      return `<tr>${tds}</tr>`;
    }).join('');
    return `<table><thead><tr>${ths}</tr></thead><tbody>${rows}</tbody></table>`;
  });

  // Headers
  html = html.replace(/^#### (.+)$/gm, '<h4>$1</h4>');
  html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
  html = html.replace(/^## (.+)$/gm, '<h2>$1</h2>');
  html = html.replace(/^# (.+)$/gm, '<h1>$1</h1>');

  // Bold + italic
  html = html.replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>');
  html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');

  // Unordered lists
  html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
  html = html.replace(/((?:<li>.*<\/li>\n?)+)/g, '<ul>$1</ul>');

  // Ordered lists
  html = html.replace(/^\d+\. (.+)$/gm, '<oli>$1</oli>');
  html = html.replace(/((?:<oli>.*<\/oli>\n?)+)/g, (match) => {
    return '<ol>' + match.replace(/<\/?oli>/g, (tag) => tag.replace('oli', 'li')) + '</ol>';
  });

  // Checkboxes
  html = html.replace(/- \[x\] (.+)/g, '<li class="check done">$1</li>');
  html = html.replace(/- \[ \] (.+)/g, '<li class="check">$1</li>');

  // Horizontal rules
  html = html.replace(/^---$/gm, '<hr>');

  // Paragraphs (double newline)
  html = html.replace(/\n\n/g, '</p><p>');

  // Single newlines (inside paragraphs -> <br>)
  html = html.replace(/\n/g, '<br>');

  // Cleanup
  html = html.replace(/<br><(h[1-4]|ul|ol|table|pre|hr)/g, '<$1');
  html = html.replace(/<\/(h[1-4]|ul|ol|table|pre|hr)><br>/g, '</$1>');

  return html;
}

function escapeHtml(str) {
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

async function loadMemory() {
  loading.value = true;
  try {
    const { data } = await monitoringApi.memory();
    files.value = data.data?.files ?? [];
    updatedAt.value = data.data?.updated_at ?? '';
    if (files.value.length && !activeFile.value) {
      activeFile.value = files.value[0].filename;
    }
  } catch (err) {
    console.error('Memory load error:', err);
  } finally {
    loading.value = false;
  }
}

onMounted(loadMemory);
</script>

<style scoped>
.prose h1 { font-size: 1.5rem; font-weight: 800; color: #0A1F44; border-bottom: 2px solid #1BA97F; padding-bottom: 6px; margin: 24px 0 12px; }
.prose h2 { font-size: 1.25rem; font-weight: 700; color: #0A1F44; border-bottom: 1px solid #eee; padding-bottom: 4px; margin: 20px 0 10px; }
.prose h3 { font-size: 1.1rem; font-weight: 600; color: #1BA97F; margin: 16px 0 8px; }
.prose h4 { font-size: 1rem; font-weight: 600; color: #333; margin: 12px 0 6px; }
.prose ul, .prose ol { padding-left: 20px; margin: 6px 0; }
.prose li { margin-bottom: 3px; line-height: 1.5; }
.prose strong { color: #0A1F44; }
.prose code { background: #f1f5f9; padding: 1px 5px; border-radius: 3px; font-size: 0.85em; font-family: 'SF Mono', Monaco, monospace; color: #e11d48; }
.prose :deep(.code-block) { background: #1e293b; color: #a7f3d0; padding: 12px 16px; border-radius: 8px; font-size: 0.8em; overflow-x: auto; margin: 10px 0; line-height: 1.5; }
.prose :deep(.code-block) code { background: transparent; color: inherit; padding: 0; font-size: inherit; }
.prose table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 0.9em; }
.prose th { background: #0A1F44; color: white; padding: 6px 10px; text-align: left; font-weight: 600; }
.prose td { padding: 5px 10px; border-bottom: 1px solid #e5e7eb; }
.prose tr:hover { background: #f8fafc; }
.prose hr { border: none; border-top: 1px solid #e5e7eb; margin: 16px 0; }
.prose p { margin: 6px 0; line-height: 1.6; }

.border-l-3 { border-left-width: 3px; }

.prose .check { list-style: none; padding-left: 0; }
.prose .check::before { content: "\2610 "; font-size: 1.1em; margin-right: 4px; }
.prose .check.done::before { content: "\2611 "; color: #16a34a; }
</style>
