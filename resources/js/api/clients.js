import api from './index';

export const clientsApi = {
    list:   (params) => api.get('/clients', { params }),
    get:    (id)     => api.get(`/clients/${id}`),
    create: (data)   => api.post('/clients', data),
    update: (id, data) => api.put(`/clients/${id}`, data),
    remove: (id)     => api.delete(`/clients/${id}`),

    getProfile:   (id)     => api.get(`/clients/${id}/profile`),
    saveProfile:  (id, data) => api.post(`/clients/${id}/profile`, data),
    getScores:    (id)     => api.get(`/clients/${id}/scoring`),
    recalculate:  (id)     => api.post(`/clients/${id}/scoring/recalculate`),
    getScore:     (id, country) => api.get(`/clients/${id}/scoring/${country}`),
};
