import { defineStore } from 'pinia';
import { ref } from 'vue';
import { casesApi } from '@/api/cases';

export const useCasesStore = defineStore('cases', () => {
    const board    = ref([]);
    const cases    = ref([]);
    const meta     = ref(null);
    const stats    = ref({ total: 0, overdue: 0, critical: 0 });
    const loading  = ref(false);

    async function fetchKanban() {
        loading.value = true;
        try {
            const { data } = await casesApi.kanban();
            board.value = data.data.board;
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

    return { board, cases, meta, stats, loading, fetchKanban, fetchList, moveStage };
});
