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

    // ----------------------------------------------------------------
    // Owner Admin (/crm) — только superadmin
    // ----------------------------------------------------------------
    {
        path: '/crm',
        component: () => import('@/layouts/OwnerLayout.vue'),
        meta: { requiresAuth: true, roles: ['superadmin'] },
        children: [
            {
                path: '',
                name: 'owner.dashboard',
                component: () => import('@/pages/owner/OwnerDashboard.vue'),
            },
            {
                path: 'agencies',
                name: 'owner.agencies',
                component: () => import('@/pages/owner/OwnerAgenciesPage.vue'),
            },
            {
                path: 'users',
                name: 'owner.users',
                component: () => import('@/pages/owner/OwnerUsersPage.vue'),
            },
            {
                path: 'leads',
                name: 'owner.leads',
                component: () => import('@/pages/owner/OwnerLeadsPage.vue'),
            },
            {
                path: 'countries',
                name: 'owner.countries',
                component: () => import('@/pages/owner/OwnerCountriesPage.vue'),
            },
            {
                path: 'documents',
                name: 'owner.documents',
                component: () => import('@/pages/owner/OwnerDocumentsPage.vue'),
            },
            {
                path: 'finance',
                name: 'owner.finance',
                component: () => import('@/pages/owner/OwnerFinancePage.vue'),
            },
            {
                path: 'services',
                name: 'owner.services',
                component: () => import('@/pages/owner/OwnerServicesPage.vue'),
            },
        ],
    },

    // ----------------------------------------------------------------
    // App (CRM для агентств) — /app
    // ----------------------------------------------------------------
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
            {
                path: 'reports',
                name: 'reports',
                component: () => import('@/pages/ReportsPage.vue'),
                meta: { roles: ['owner', 'superadmin'] },
            },
            {
                path: 'services',
                name: 'services',
                component: () => import('@/pages/AgencyServicesPage.vue'),
                meta: { roles: ['owner', 'superadmin'] },
            },
            {
                path: 'overdue',
                name: 'overdue',
                component: () => import('@/pages/OverdueCasesPage.vue'),
            },
            {
                path: 'settings',
                name: 'settings',
                component: () => import('@/pages/AgencySettingsPage.vue'),
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

    // Гость не должен попасть на /login если уже вошёл
    if (to.meta.guest && auth.isLoggedIn) {
        return auth.user?.role === 'superadmin'
            ? { name: 'owner.dashboard' }
            : { name: 'dashboard' };
    }

    // Проверка ролей на маршруте
    if (to.meta.roles && auth.user?.role && !to.meta.roles.includes(auth.user.role)) {
        // Суперадмин → owner dashboard, остальные → dashboard
        return auth.user.role === 'superadmin'
            ? { name: 'owner.dashboard' }
            : { name: 'dashboard' };
    }
});

export default router;
