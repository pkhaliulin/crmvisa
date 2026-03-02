import api from './index';

export const countriesApi = {
    list:      () => api.get('/countries'),
    visaTypes: () => api.get('/visa-types'),
    visaSettingsPublic: (code) => api.get(`/countries/${code}/visa-settings`),
};

// Owner (superadmin) API
export const ownerCountriesApi = {
    list:  () => api.get('/owner/countries'),
    store: (data) => api.post('/owner/countries', data),
    update: (code, data) => api.patch(`/owner/countries/${code}`, data),

    // Country detail
    detail: (code) => api.get(`/owner/countries/${code}/detail`),

    // Embassy
    updateEmbassy: (code, data) => api.patch(`/owner/countries/${code}/embassy`, data),

    // Visa type settings
    visaSettings:       (code) => api.get(`/owner/countries/${code}/visa-settings`),
    visaSettingStore:    (code, data) => api.post(`/owner/countries/${code}/visa-settings`, data),
    visaSettingUpdate:   (code, id, data) => api.patch(`/owner/countries/${code}/visa-settings/${id}`, data),
    visaSettingDestroy:  (code, id) => api.delete(`/owner/countries/${code}/visa-settings/${id}`),

    // Requirements (documents per country+visa_type)
    requirements: (code) => api.get(`/owner/countries/${code}/requirements`),
    requirementStore:   (data) => api.post('/admin/country-requirements', data),
    requirementUpdate:  (id, data) => api.patch(`/admin/country-requirements/${id}`, data),
    requirementDestroy: (id) => api.delete(`/admin/country-requirements/${id}`),

    // Document templates (for dropdown)
    documentTemplates: () => api.get('/admin/document-templates'),

    // Scoring
    scoring:       (code) => api.get(`/owner/countries/${code}/scoring`),
    updateScoring: (code, data) => api.patch(`/owner/countries/${code}/scoring`, data),

    // Analytics
    stats: (code) => api.get(`/owner/countries/${code}/stats`),
};
