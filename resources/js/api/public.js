import axios from 'axios';

// Отдельный axios без авто-редиректа на /login
const publicApi = axios.create({
    baseURL: '/api/v1/public',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
});

publicApi.interceptors.request.use((config) => {
    const token = localStorage.getItem('public_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    const locale = localStorage.getItem('locale') || 'ru';
    config.headers['X-Locale'] = locale;
    return config;
});

publicApi.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('public_token');
            localStorage.removeItem('public_user');
            window.location.href = '/';
        }
        return Promise.reject(error);
    }
);

export const publicPortalApi = {
    // Auth
    sendOtp:   (phone)       => publicApi.post('/auth/send-otp', { phone }),
    verifyOtp: (phone, code) => publicApi.post('/auth/verify-otp', { phone, code }),
    loginPin:  (phone, pin)  => publicApi.post('/auth/login', { phone, pin }),
    setPin:    (pin)         => publicApi.post('/auth/set-pin', { pin }),

    // Profile
    me:             ()       => publicApi.get('/me'),
    updateProfile:  (data)   => publicApi.patch('/me', data),
    uploadPassport: (file)   => {
        const fd = new FormData();
        fd.append('passport', file);
        return publicApi.post('/me/passport', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    },

    // Cases
    cases:      ()     => publicApi.get('/me/cases'),
    createCase: (data) => publicApi.post('/me/cases', data),
    caseDetail: (id)   => publicApi.get(`/me/cases/${id}`),
    uploadChecklistItem: (caseId, itemId, formData) =>
        publicApi.post(`/me/cases/${caseId}/checklist/${itemId}/upload`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        }),

    // Agencies & Leads
    agencies:   (params) => publicApi.get('/agencies', { params }),
    submitLead: (data)   => publicApi.post('/leads', data),

    // Reviews
    agencyReviews:  (agencyId, params) => publicApi.get(`/agencies/${agencyId}/reviews`, { params }),
    submitReview:   (agencyId, data)   => publicApi.post(`/agencies/${agencyId}/reviews`, data),
    canReview:      (agencyId)         => publicApi.get(`/me/can-review/${agencyId}`),

    // Scoring
    countries:    ()     => publicApi.get('/countries'),
    scoreAll:     ()     => publicApi.get('/scoring'),
    scoreCountry: (code) => publicApi.get(`/scoring/${code}`),

    // References (public, no auth)
    publicReferences: () => publicApi.get('/references'),
};
