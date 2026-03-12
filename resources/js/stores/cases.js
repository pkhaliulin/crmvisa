import { defineStore } from 'pinia';
import { ref } from 'vue';
import { casesApi } from '@/api/cases';

// Fallback — используется ТОЛЬКО если бэкенд не вернул allowed_transitions
const FALLBACK_TRANSITIONS = {
    lead:          ['qualification'],
    qualification: ['documents'],
    documents:     ['qualification', 'doc_review'],
    doc_review:    ['documents', 'translation', 'ready'],
    translation:   ['doc_review', 'ready'],
    ready:         ['translation', 'review'],
    review:        ['ready', 'result'],
    result:        ['lead'],
};

export const useCasesStore = defineStore('cases', () => {
    const board              = ref([]);
    const cases              = ref([]);
    const meta               = ref(null);
    const stats              = ref({ total: 0, overdue: 0, critical: 0 });
    const allowedTransitions = ref(FALLBACK_TRANSITIONS);
    const loading            = ref(false);

    async function fetchKanban() {
        loading.value = true;
        try {
            const { data } = await casesApi.kanban();
            board.value = data.data.board;
            const transitions = data.data.allowed_transitions;
            allowedTransitions.value = transitions && Object.keys(transitions).length
                ? transitions
                : FALLBACK_TRANSITIONS;
            stats.value = {
                total:    data.data.total,
                overdue:  data.data.overdue,
                critical: data.data.critical,
                role:     data.data.role,
            };
        } finally {
            loading.value = false;
        }
    }

    async function fetchList(params = {}) {
        loading.value = true;
        try {
            const { data } = await casesApi.list(params);
            cases.value = data.data.data;
            meta.value  = data.data.meta;
        } finally {
            loading.value = false;
        }
    }

    async function moveStage(caseId, stage, notes = null) {
        await casesApi.moveStage(caseId, { stage, notes });
        await fetchKanban();
    }

    return { board, cases, meta, stats, allowedTransitions, loading, fetchKanban, fetchList, moveStage };
});
