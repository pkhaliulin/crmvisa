import api from './index';

export const dashboardApi = {
    index:         (params) => api.get('/dashboard', { params }),
    overdue:       () => api.get('/dashboard/overdue'),
    managerCases:  (id) => api.get(`/dashboard/managers/${id}/cases`),
};
