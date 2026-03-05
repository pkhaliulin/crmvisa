import api from './index';

export const usersApi = {
    list:           ()           => api.get('/users'),
    show:           (id)         => api.get(`/users/${id}`),
    create:         (data)       => api.post('/users', data, {
        headers: { 'Content-Type': 'multipart/form-data' },
    }),
    update:         (id, data)   => api.patch(`/users/${id}`, data),
    updateAvatar:   (id, data)   => api.post(`/users/${id}`, data, {
        headers: { 'Content-Type': 'multipart/form-data' },
        params: { _method: 'PATCH' },
    }),
    resetPassword:  (id, data)   => api.post(`/users/${id}/password`, data),
    remove:         (id)         => api.delete(`/users/${id}`),
};
