import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { usePublicAuthStore } from '@/stores/publicAuth';

const routes = [
    // ----------------------------------------------------------------
    // Публичный лендинг (visabor.uz)
    // ----------------------------------------------------------------
    {
        path: '/',
        name: 'landing',
        component: () => import('@/pages/LandingPage.vue'),
        meta: { public: true },
    },
    {
        path: '/scoring',
        name: 'public.scoring',
        component: () => import('@/pages/public/PublicScoringPage.vue'),
        meta: { public: true, requiresPublicAuth: true },
    },

    // Auth
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/auth/LoginPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/pages/auth/RegisterPage.vue'),
        meta: { guest: true },
    },

    // App (CRM)
    {
        path: '/app',
        component: () => import('@/layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('@/pages/DashboardPage.vue'),
            },
            {
                path: 'kanban',
                name: 'kanban',
                component: () => import('@/pages/KanbanPage.vue'),
            },
            {
                path: 'cases',
                name: 'cases',
                component: () => import('@/pages/cases/CasesPage.vue'),
            },
            {
                path: 'cases/new',
                name: 'cases.create',
                component: () => import('@/pages/cases/CaseFormPage.vue'),
            },
            {
                path: 'cases/:id',
                name: 'cases.show',
                component: () => import('@/pages/cases/CaseDetailPage.vue'),
            },
            {
                path: 'clients',
                name: 'clients',
                component: () => import('@/pages/clients/ClientsPage.vue'),
            },
            {
                path: 'clients/new',
                name: 'clients.create',
                component: () => import('@/pages/clients/ClientFormPage.vue'),
            },
            {
                path: 'clients/:id',
                name: 'clients.show',
                component: () => import('@/pages/clients/ClientDetailPage.vue'),
            },
            {
                path: 'users',
                name: 'users',
                component: () => import('@/pages/UsersPage.vue'),
                meta: { roles: ['owner', 'superadmin'] },
            },
        ],
    },

    // Fallback
    { path: '/:pathMatch(.*)*', redirect: { name: 'landing' } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach((to) => {
    const auth       = useAuthStore();
    const publicAuth = usePublicAuthStore();

    // Публичный портал — защита скоринга
    if (to.meta.requiresPublicAuth && !publicAuth.isLoggedIn) {
        return { name: 'landing' };
    }

    // CRM — требует JWT
    if (to.meta.requiresAuth && !auth.isLoggedIn) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && auth.isLoggedIn) {
        return { name: 'dashboard' };
    }

    if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
        return { name: 'dashboard' };
    }
});

export default router;
