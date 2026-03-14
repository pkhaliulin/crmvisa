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
    changePhoneSendOtp: (phone) => publicApi.post('/me/change-phone/send-otp', { phone }),
    changePhoneVerify:  (phone, code) => publicApi.post('/me/change-phone/verify', { phone, code }),
    saveEmail:          (email) => publicApi.post('/me/email', { recovery_email: email }),
    verifyEmail:        (code)  => publicApi.post('/me/email/verify', { code }),
    uploadPassport: (file)   => {
        const fd = new FormData();
        fd.append('passport', file);
        return publicApi.post('/me/passport', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    },
    passportData: () => publicApi.get('/me/passport-data'),
    ocrFromCase:  () => publicApi.post('/me/passport-from-case'),

    // Cases
    cases:      ()     => publicApi.get('/me/cases'),
    createCase: (data) => publicApi.post('/me/cases', data),
    caseDetail: (id)   => publicApi.get(`/me/cases/${id}`),
    updateCase: (id, data) => publicApi.patch(`/me/cases/${id}`, data),
    cancelCase: (id) => publicApi.post(`/me/cases/${id}/cancel`),
    caseActivities: (id) => publicApi.get(`/me/cases/${id}/activities`),
    uploadChecklistItem: (caseId, itemId, formData) =>
        publicApi.post(`/me/cases/${caseId}/checklist/${itemId}/upload`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        }),
    checkChecklistItem: (caseId, itemId, checked, status = null) =>
        publicApi.patch(`/me/cases/${caseId}/checklist/${itemId}/check`, { checked, ...(status ? { status } : {}) }),
    repeatChecklistItem: (caseId, itemId) =>
        publicApi.post(`/me/cases/${caseId}/checklist/${itemId}/repeat`),
    deleteChecklistItem: (caseId, itemId) =>
        publicApi.delete(`/me/cases/${caseId}/checklist/${itemId}`),
    previewDocument: (documentId) =>
        publicApi.get(`/me/documents/${documentId}/preview`),

    // Family
    familyMembers:      ()            => publicApi.get('/me/family'),
    addFamilyMember:    (data)        => publicApi.post('/me/family', data),
    updateFamilyMember: (id, data)    => publicApi.patch(`/me/family/${id}`, data),
    deleteFamilyMember: (id)          => publicApi.delete(`/me/family/${id}`),
    caseFamilyMembers:  (caseId)      => publicApi.get(`/me/cases/${caseId}/family`),
    attachFamilyToCase: (caseId, fid) => publicApi.post(`/me/cases/${caseId}/family`, { family_member_id: fid }),
    detachFamilyFromCase: (caseId, fid) => publicApi.delete(`/me/cases/${caseId}/family/${fid}`),

    // Agencies & Leads
    agencies:       (params) => publicApi.get('/agencies', { params }),
    agencyDetail:   (id)     => publicApi.get(`/agencies/${id}`),
    submitLead:     (data)   => publicApi.post('/leads', data),
    caseAgencies:   (caseId) => publicApi.get(`/me/cases/${caseId}/agencies`),
    changeAgency:   (caseId) => publicApi.post(`/me/cases/${caseId}/change-agency`),

    // Payments
    initiatePayment: (data)   => publicApi.post('/me/payments/initiate', data),
    markAsPaid:      (data)   => publicApi.post('/me/payments/mark-paid', data),
    paymentStatus:   (caseId) => publicApi.get(`/me/cases/${caseId}/payment`),
    billingHistory:  (params) => publicApi.get('/me/billing', { params }),

    // Reviews
    agencyReviews:  (agencyId, params) => publicApi.get(`/agencies/${agencyId}/reviews`, { params }),
    submitReview:   (agencyId, data)   => publicApi.post(`/agencies/${agencyId}/reviews`, data),
    canReview:      (agencyId)         => publicApi.get(`/me/can-review/${agencyId}`),

    // Groups
    groups:            ()              => publicApi.get('/me/groups'),
    createGroup:       (data)          => publicApi.post('/me/groups', data),
    groupDetail:       (id)            => publicApi.get(`/me/groups/${id}`),
    addGroupMember:    (id, data)      => publicApi.post(`/me/groups/${id}/members`, data),
    removeGroupMember: (id, mid)       => publicApi.delete(`/me/groups/${id}/members/${mid}`),
    setGroupAgency:    (id, agencyId)  => publicApi.post(`/me/groups/${id}/agency`, { agency_id: agencyId }),
    groupAgencies:     (id)            => publicApi.get(`/me/groups/${id}/agencies`),
    memberCaseDetail:  (id, memberId)  => publicApi.get(`/me/groups/${id}/members/${memberId}/case`),
    payForGroup:       (id, provider)  => publicApi.post(`/me/groups/${id}/pay`, { provider }),

    // Scoring
    servedCountries: ()  => publicApi.get('/served-countries'),
    countries:    ()     => publicApi.get('/countries'),
    countryDetail:(code) => publicApi.get(`/countries/${code}`),
    scoreAll:     ()     => publicApi.get('/scoring'),
    scoreCountry: (code) => publicApi.get(`/scoring/${code}`),
    scoreProfile: ()     => publicApi.get('/scoring/profile'),
    scoreBatch:   (countries, visaType = 'tourist') => publicApi.post('/scoring/batch', { countries, visa_type: visaType }),

    // Recovery (no auth)
    recoveryRequest:     (phone) => publicApi.post('/recovery/request', { phone }),
    recoveryVerifyToken: (token) => publicApi.post('/recovery/verify-token', { token }),
    recoverySendOtp:     (token, phone) => publicApi.post('/recovery/send-otp', { token, phone }),
    recoveryConfirm:     (token, phone, code) => publicApi.post('/recovery/confirm', { token, phone, code }),

    // References (public, no auth)
    publicReferences: () => publicApi.get('/references'),
};
