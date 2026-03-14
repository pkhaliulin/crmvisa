import api from './index';

export const clientsApi = {
    list:   (params) => api.get('/clients', { params }),
    get:    (id)     => api.get(`/clients/${id}`),
    create: (data)   => api.post('/clients', data),
    update: (id, data) => api.put(`/clients/${id}`, data),
    remove: (id)     => api.delete(`/clients/${id}`),

    parsePassport: (file) => {
        const fd = new FormData();
        fd.append('file', file);
        return api.post('/clients/parse-passport', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    },
    applyAiData:     (id)       => api.post(`/clients/${id}/apply-ai-data`),
    visaborScoring:  (id)       => api.get(`/clients/${id}/visabor-scoring`),
    getProfile:      (id)       => api.get(`/clients/${id}/profile`),
    updateProfile:   (id, data) => api.patch(`/clients/${id}/profile`, data),
};
