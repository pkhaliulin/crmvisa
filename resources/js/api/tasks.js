import api from './index';

export const tasksApi = {
    list:       (params = {}) => api.get('/tasks', { params }),
    show:       (id)          => api.get(`/tasks/${id}`),
    create:     (data)        => api.post('/tasks', data),
    update:     (id, data)    => api.patch(`/tasks/${id}`, data),
    remove:     (id)          => api.delete(`/tasks/${id}`),
    transition: (id)          => api.post(`/tasks/${id}/transition`),
    setStatus:  (id, data)    => api.post(`/tasks/${id}/set-status`, data),
    counters:   ()            => api.get('/tasks/counters'),
};
