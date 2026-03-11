<?php

/**
 * Permission Matrix — гранулярная матрица прав по ролям.
 *
 * Используется для документирования и проверки в Policy/Middleware.
 * Все owner-only эндпоинты защищены middleware role:owner,superadmin.
 * Granular контроль внутри ролей через Laravel Policies.
 */
return [
    // =====================================================================
    // SUPERADMIN (суперадмин платформы VisaBor)
    // =====================================================================
    'superadmin' => [
        'cases'       => ['view_all', 'create', 'update', 'delete', 'move_stage', 'reassign', 'cancel', 'complete'],
        'clients'     => ['view_all', 'create', 'update', 'delete', 'export'],
        'users'       => ['view_all', 'create', 'update', 'delete', 'reset_password'],
        'documents'   => ['view_all', 'upload', 'update_status', 'delete', 'download_zip'],
        'reports'     => ['overview', 'managers', 'countries', 'overdue', 'sla_performance'],
        'billing'     => ['view', 'change_plan', 'cancel', 'manage_wallet', 'view_invoices'],
        'settings'    => ['view', 'update', 'work_countries', 'api_key', 'notifications'],
        'services'    => ['view', 'create', 'update', 'delete'],
        'leads'       => ['view', 'analytics', 'channels', 'connect', 'disconnect'],
        'marketplace' => ['view_profile', 'update_profile', 'view_leads', 'update_lead_status'],
        'knowledge'   => ['view', 'create', 'update', 'delete', 'pin', 'share'],
        'owner_panel' => ['full_access'],
    ],

    // =====================================================================
    // OWNER (владелец агентства)
    // =====================================================================
    'owner' => [
        'cases'       => ['view_all', 'create', 'update', 'delete', 'move_stage', 'reassign', 'cancel', 'complete'],
        'clients'     => ['view_all', 'create', 'update', 'delete'],
        'users'       => ['view_all', 'create', 'update', 'delete', 'reset_password'],
        'documents'   => ['view_all', 'upload', 'update_status', 'delete', 'download_zip'],
        'reports'     => ['overview', 'managers', 'countries', 'overdue', 'sla_performance'],
        'billing'     => ['view', 'change_plan', 'cancel', 'view_invoices'],
        'settings'    => ['view', 'update', 'work_countries', 'api_key', 'notifications'],
        'services'    => ['view', 'create', 'update', 'delete'],
        'leads'       => ['view', 'analytics', 'channels', 'connect', 'disconnect'],
        'marketplace' => ['view_profile', 'update_profile', 'view_leads', 'update_lead_status'],
        'knowledge'   => ['view', 'create', 'update', 'delete', 'pin', 'share'],
        'owner_panel' => [],
    ],

    // =====================================================================
    // MANAGER (менеджер агентства)
    // =====================================================================
    'manager' => [
        'cases'       => ['view_own', 'create', 'update_own', 'move_stage_own', 'complete_own'],
        'clients'     => ['view_all', 'create', 'update_own'],
        'users'       => [],
        'documents'   => ['view_own', 'upload_own', 'update_status_own'],
        'reports'     => [],
        'billing'     => [],
        'settings'    => [],
        'services'    => ['view'],
        'leads'       => [],
        'marketplace' => [],
        'knowledge'   => ['view'],
        'owner_panel' => [],
    ],

    // =====================================================================
    // CLIENT (клиент публичного портала)
    // =====================================================================
    'client' => [
        'cases'       => ['view_own', 'create_draft', 'update_draft', 'cancel_draft'],
        'profile'     => ['view', 'update', 'upload_passport'],
        'family'      => ['view', 'create', 'update', 'delete', 'attach_to_case', 'detach_from_case'],
        'documents'   => ['upload_own', 'view_own'],
        'scoring'     => ['view_own', 'score_countries'],
        'groups'      => ['view_own', 'create', 'add_member', 'set_agency', 'pay'],
        'agencies'    => ['view_marketplace', 'view_detail', 'send_lead', 'write_review'],
        'payments'    => ['initiate', 'view_history'],
        'billing'     => ['view_own'],
    ],
];
