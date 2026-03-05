import api from './index';

export const usersApi = {
    list:   ()           => api.get('/users'),
    create: (data)       => api.post('/users', data, {
        headers: { 'Content-Type': 'multipart/form-data' },
    }),
    update: (id, data)   => api.patch(`/users/${id}`, data),
    remove: (id)         => api.delete(`/users/${id}`),
};
