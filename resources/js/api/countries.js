import api from './index';

export const countriesApi = {
    list:      () => api.get('/countries'),
    visaTypes: () => api.get('/visa-types'),
};
