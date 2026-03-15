import api from './index';

export const casesApi = {
    list:       (params) => api.get('/cases', { params }),
    get:        (id)     => api.get(`/cases/${id}`),
    create:     (data)   => api.post('/cases', data),
    update:     (id, data) => api.put(`/cases/${id}`, data),
    assign:     (id, assigned_to) => api.patch(`/cases/${id}`, { assigned_to }),
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

    // Workflow
    complete:           (id, data) => api.post(`/cases/${id}/complete`, data),
    submitToEmbassy:    (id, data) => api.post(`/cases/${id}/submit-to-embassy`, data),
    updateExpectedDate: (id, data) => api.patch(`/cases/${id}/expected-date`, data),

    // Translation
    uploadTranslation:  (caseId, itemId, form) => api.post(`/cases/${caseId}/checklist/${itemId}/upload-translation`, form, { headers: { 'Content-Type': 'multipart/form-data' } }),
    approveTranslation: (caseId, itemId)       => api.patch(`/cases/${caseId}/checklist/${itemId}/approve-translation`),

    // AI-анализ документов
    aiAnalyze:    (caseId, itemId) => api.post(`/cases/${caseId}/checklist/${itemId}/ai-analyze`),
    aiRiskScore:  (caseId) => api.get(`/cases/${caseId}/ai-risk`),

    // AI-генерация документов
    generateDocTypes: (caseId) => api.get(`/cases/${caseId}/generate-document/types`),
    generateDoc:      (caseId, data) => api.post(`/cases/${caseId}/generate-document`, data),

    // Bulk actions
    bulkAssign:   (data) => api.post('/cases/bulk/assign', data),
    bulkPriority: (data) => api.post('/cases/bulk/priority', data),
    bulkExport:   (data) => api.post('/cases/bulk/export', data, { responseType: 'blob' }),

    // Cancel
    cancel:       (id, reason) => api.post(`/cases/${id}/cancel`, { reason }),

    // Activities (timeline)
    activities:   (caseId, params) => api.get(`/cases/${caseId}/activities`, { params }),
};

// Notifications API
export const notificationsApi = {
    list:         (params) => api.get('/notifications', { params }),
    unreadCount:  ()       => api.get('/notifications/unread-count'),
    markAsRead:   (id)     => api.post(`/notifications/${id}/read`),
    markAllRead:  ()       => api.post('/notifications/read-all'),
};
