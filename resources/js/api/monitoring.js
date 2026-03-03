import api from './index';

export const monitoringApi = {
    health:    () => api.get('/owner/monitoring/health'),
    errors:    (period) => api.get('/owner/monitoring/errors', { params: { period } }),
    activity:  (period) => api.get('/owner/monitoring/activity', { params: { period } }),
    metrics:   (period) => api.get('/owner/monitoring/metrics', { params: { period } }),
    alerts:    () => api.get('/owner/monitoring/alerts'),
    queue:     () => api.get('/owner/monitoring/queue'),
    retryJob:  (id) => api.post(`/owner/monitoring/queue/${id}/retry`),
    deleteJob: (id) => api.delete(`/owner/monitoring/queue/${id}`),
};
