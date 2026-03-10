import api from './index';

export const caseEngineApi = {
    // Readiness + Missing Items + Next Action
    readiness:     (caseId) => api.get(`/cases/${caseId}/engine/readiness`),

    // Чекпоинты
    checkpoints:   (caseId) => api.get(`/cases/${caseId}/engine/checkpoints`),
    toggleCheckpoint: (caseId, cpId, data) => api.patch(`/cases/${caseId}/engine/checkpoints/${cpId}`, data),

    // Анкета
    getForm:       (caseId) => api.get(`/cases/${caseId}/engine/form`),
    saveFormStep:  (caseId, step, data) => api.put(`/cases/${caseId}/engine/form/${step}`, { data }),
    prefillForm:   (caseId) => api.post(`/cases/${caseId}/engine/form/prefill`),
    formProgress:  (caseId) => api.get(`/cases/${caseId}/engine/form/progress`),

    // Инициализация engine
    initialize:    (caseId) => api.post(`/cases/${caseId}/engine/init`),

    // Справочник правил
    getRules:      () => api.get('/engine/rules'),
    getRuleDetail: (cc, vt) => api.get(`/engine/rules/${cc}/${vt}`),
};
