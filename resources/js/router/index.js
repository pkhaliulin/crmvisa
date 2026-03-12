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

    // Восстановление доступа
    {
        path: '/recovery',
        name: 'recovery',
        component: () => import('@/pages/public/RecoveryPage.vue'),
        meta: { public: true },
    },
    {
        path: '/recovery/verify',
        name: 'recovery.verify',
        component: () => import('@/pages/public/RecoveryVerifyPage.vue'),
        meta: { public: true },
    },

    // Личный кабинет публичного пользователя
    {
        path: '/me',
        component: () => import('@/layouts/ClientPortalLayout.vue'),
        meta: { requiresPublicAuth: true },
        children: [
            { path: '', redirect: { name: 'me.cases' } },
            {
                path: 'profile',
                name: 'me.profile',
                component: () => import('@/pages/public/PublicProfilePage.vue'),
            },
            {
                path: 'scoring',
                name: 'me.scoring',
                component: () => import('@/pages/public/PublicScoringPage.vue'),
            },
            {
                path: 'cases',
                name: 'me.cases',
                component: () => import('@/pages/public/PublicCasesPage.vue'),
            },
            {
                path: 'cases/:id',
                name: 'me.cases.show',
                component: () => import('@/pages/public/PublicCaseDetailPage.vue'),
            },
            {
                path: 'groups',
                name: 'me.groups',
                component: () => import('@/pages/public/PublicGroupsPage.vue'),
            },
            {
                path: 'groups/:id',
                name: 'me.groups.show',
                component: () => import('@/pages/public/PublicGroupDetailPage.vue'),
            },
            {
                path: 'agencies',
                name: 'me.agencies',
                component: () => import('@/pages/public/PublicAgenciesPage.vue'),
            },
            {
                path: 'agencies/:id',
                name: 'me.agencies.show',
                component: () => import('@/pages/public/PublicAgencyDetailPage.vue'),
            },
            {
                path: 'billing',
                name: 'me.billing',
                component: () => import('@/pages/public/PublicBillingPage.vue'),
            },
            {
                path: 'countries',
                name: 'me.countries',
                component: () => import('@/pages/public/PublicCountriesPage.vue'),
            },
            {
                path: 'countries/:code',
                name: 'me.countries.show',
                component: () => import('@/pages/public/PublicCountryDetailPage.vue'),
            },
        ],
    },

    // Обратная совместимость — старый /scoring редиректит в новый кабинет
    {
        path: '/scoring',
        redirect: '/me/scoring',
        meta: { requiresPublicAuth: true },
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
                path: 'countries/:code',
                name: 'owner.country.detail',
                component: () => import('@/pages/owner/OwnerCountryDetailPage.vue'),
            },
            {
                path: 'visa-types',
                name: 'owner.visa-types',
                component: () => import('@/pages/owner/OwnerVisaTypesPage.vue'),
            },
            {
                path: 'references',
                name: 'owner.references',
                component: () => import('@/pages/owner/OwnerReferencesPage.vue'),
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
            {
                path: 'crm-users',
                name: 'owner.crm-users',
                component: () => import('@/pages/owner/OwnerCrmUsersPage.vue'),
            },
            {
                path: 'monitoring',
                name: 'owner.monitoring',
                component: () => import('@/pages/owner/OwnerMonitoringPage.vue'),
            },
            {
                path: 'billing',
                name: 'owner.billing',
                component: () => import('@/pages/owner/OwnerBillingPage.vue'),
            },
            {
                path: 'lead-channels',
                name: 'owner.lead-channels',
                component: () => import('@/pages/owner/OwnerLeadChannelsPage.vue'),
            },
            {
                path: 'lead-channels/:id',
                name: 'owner.lead-channels.detail',
                component: () => import('@/pages/owner/OwnerLeadChannelDetailPage.vue'),
            },
            {
                path: 'memory',
                name: 'owner.memory',
                component: () => import('@/pages/owner/OwnerMemoryPage.vue'),
            },
            {
                path: 'knowledge',
                name: 'owner.knowledge',
                component: () => import('@/pages/owner/OwnerKnowledgePage.vue'),
            },
            {
                path: 'website',
                name: 'owner.website',
                component: () => import('@/pages/owner/OwnerWebsitePage.vue'),
            },
        ],
    },

    // /memory → redirect в CRM (superadmin only)
    {
        path: '/memory',
        redirect: '/crm/memory',
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
                path: 'users/:id',
                name: 'users.show',
                component: () => import('@/pages/UserDetailPage.vue'),
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
                path: 'services/:id',
                name: 'service.detail',
                component: () => import('@/pages/AgencyServiceDetailPage.vue'),
                meta: { roles: ['owner', 'superadmin'] },
            },
            {
                path: 'notifications',
                name: 'notifications',
                component: () => import('@/pages/NotificationsPage.vue'),
            },
            {
                path: 'tasks',
                name: 'tasks',
                component: () => import('@/pages/TasksPage.vue'),
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
            {
                path: 'billing',
                name: 'billing',
                component: () => import('@/pages/BillingPage.vue'),
                meta: { roles: ['owner', 'superadmin'] },
            },
            {
                path: 'countries',
                name: 'countries',
                component: () => import('@/pages/AgencyCountriesPage.vue'),
                meta: { roles: ['owner', 'manager', 'superadmin'] },
            },
            {
                path: 'countries/:code',
                name: 'countries.show',
                component: () => import('@/pages/AgencyCountryDetailPage.vue'),
                meta: { roles: ['owner', 'manager', 'superadmin'] },
            },
            {
                path: 'knowledge',
                name: 'knowledge',
                component: () => import('@/pages/KnowledgePage.vue'),
            },
            {
                path: 'leadgen',
                name: 'leadgen',
                component: () => import('@/pages/leadgen/LeadGenPage.vue'),
                meta: { roles: ['owner', 'superadmin'] },
            },
            {
                path: 'leadgen/analytics',
                name: 'leadgen.analytics',
                component: () => import('@/pages/leadgen/LeadAnalyticsPage.vue'),
                meta: { roles: ['owner', 'superadmin'] },
            },
            {
                path: 'leadgen/notifications',
                name: 'leadgen.notifications',
                component: () => import('@/pages/leadgen/NotificationSettingsPage.vue'),
                meta: { roles: ['owner', 'superadmin'] },
            },
            {
                path: 'leadgen/:code',
                name: 'leadgen.detail',
                component: () => import('@/pages/leadgen/LeadGenDetailPage.vue'),
                meta: { roles: ['owner', 'superadmin'] },
            },
        ],
    },

    // Fallback
    { path: '/:pathMatch(.*)*', redirect: { name: 'landing' } },
];

// Обёртка для lazy import — при ошибке загрузки chunk (после деплоя) перезагружает страницу
function lazyLoad(importFn) {
    return () => importFn().catch((err) => {
        // Уже пробовали reload — не зацикливаемся
        const key = '__chunk_reload__';
        if (!sessionStorage.getItem(key)) {
            sessionStorage.setItem(key, '1');
            window.location.reload();
            return;
        }
        sessionStorage.removeItem(key);
        throw err;
    });
}

// Применяем lazyLoad ко всем lazy-компонентам
function wrapRoutes(routeList) {
    return routeList.map((route) => {
        const wrapped = { ...route };
        if (typeof wrapped.component === 'function') {
            wrapped.component = lazyLoad(wrapped.component);
        }
        if (wrapped.children) {
            wrapped.children = wrapRoutes(wrapped.children);
        }
        return wrapped;
    });
}

const router = createRouter({
    history: createWebHistory(),
    routes: wrapRoutes(routes),
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

    // Суперадмин без agency_id не должен быть в /app (CRM агентства)
    if (to.path.startsWith('/app') && auth.user?.role === 'superadmin' && !auth.user?.agency_id) {
        return { name: 'owner.dashboard' };
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
