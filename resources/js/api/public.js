import axios from 'axios';

// Отдельный axios без авто-редиректа на /login
const publicApi = axios.create({
    baseURL: '/api/v1/public',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
});

publicApi.interceptors.request.use((config) => {
    const token = localStorage.getItem('public_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

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

    // Scoring
    countries:    ()     => publicApi.get('/countries'),
    scoreAll:     ()     => publicApi.get('/scoring'),
    scoreCountry: (code) => publicApi.get(`/scoring/${code}`),
};
