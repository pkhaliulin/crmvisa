import api from './index';

export const casesApi = {
    list:       (params) => api.get('/cases', { params }),
    get:        (id)     => api.get(`/cases/${id}`),
    create:     (data)   => api.post('/cases', data),
    update:     (id, data) => api.put(`/cases/${id}`, data),
    remove:     (id)     => api.delete(`/cases/${id}`),
    moveStage:  (id, data) => api.post(`/cases/${id}/move-stage`, data),
    critical:   ()       => api.get('/cases/critical'),
    kanban:     ()       => api.get('/kanban'),

    // Документы (legacy)
    getDocuments:    (caseId)              => api.get(`/cases/${caseId}/documents`),
    uploadDocument:  (caseId, form)        => api.post(`/cases/${caseId}/documents`, form, { headers: { 'Content-Type': 'multipart/form-data' } }),
    updateDocStatus: (caseId, docId, data) => api.patch(`/cases/${caseId}/documents/${docId}`, data),
    deleteDocument:  (caseId, docId)       => api.delete(`/cases/${caseId}/documents/${docId}`),

    // Чек-лист документов
    getChecklist:       (caseId)            => api.get(`/cases/${caseId}/checklist`),
    addChecklistItem:   (caseId, data)      => api.post(`/cases/${caseId}/checklist`, data),
    uploadToSlot:       (caseId, itemId, form) => api.post(`/cases/${caseId}/checklist/${itemId}/upload`, form, { headers: { 'Content-Type': 'multipart/form-data' } }),
    checkSlot:          (caseId, itemId, checked) => api.patch(`/cases/${caseId}/checklist/${itemId}/check`, { checked }),
    reviewSlot:         (caseId, itemId, data)    => api.patch(`/cases/${caseId}/checklist/${itemId}/review`, data),
    deleteChecklistItem:(caseId, itemId)    => api.delete(`/cases/${caseId}/checklist/${itemId}`),
    downloadAllZip:     (caseId)            => api.get(`/cases/${caseId}/documents/zip`, { responseType: 'blob' }),
};
