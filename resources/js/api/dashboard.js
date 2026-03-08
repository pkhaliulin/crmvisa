import api from './index';

export const dashboardApi = {
    index:            (params) => api.get('/dashboard', { params }),
    overdue:          () => api.get('/dashboard/overdue'),
    managerCases:     (id) => api.get(`/dashboard/managers/${id}/cases`),
    goals:            (params) => api.get('/dashboard/goals', { params }),
    saveGoal:         (data) => api.post('/dashboard/goals', data),
    activityFeed:     () => api.get('/dashboard/activity'),
    financialSummary: (params) => api.get('/dashboard/financial', { params }),
};
