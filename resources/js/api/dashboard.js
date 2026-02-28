import api from './index';

export const dashboardApi = {
    index:         () => api.get('/dashboard'),
    overdue:       () => api.get('/dashboard/overdue'),
    managerCases:  (id) => api.get(`/dashboard/managers/${id}/cases`),
};
