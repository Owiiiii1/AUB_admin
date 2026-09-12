<?php

return [

    /*
    | Canonical admin menu registry for role-based access.
    | menu_key is stored in role_menu_items; route_patterns grant route access.
    */
    'items' => [
        'dashboard' => [
            'route_name' => 'dashboard',
            'route_patterns' => ['dashboard'],
            'admin_only' => false,
        ],
        'students' => [
            'route_name' => 'customers.index',
            'route_patterns' => ['customers.*'],
            'admin_only' => false,
        ],
        'teachers' => [
            'route_name' => 'teachers.index',
            'route_patterns' => ['teachers.*'],
            'admin_only' => false,
        ],
        'settings' => [
            'route_name' => 'settings.index',
            'route_patterns' => ['settings.*', 'roles.*', 'ai-settings.*'],
            'admin_only' => true,
        ],
        'statistics' => [
            'route_name' => 'statistics.logs',
            'route_patterns' => ['statistics.*'],
            'admin_only' => false,
        ],
    ],

    /*
    | Routes always allowed for any authenticated user with a role.
    */
    'always_allowed_route_patterns' => [
        'profile.*',
        'password.update',
        'logout',
        'workplace',
        'courses-groups.*',
        'lessons.*',
        'placeholder.*',
        'weekly-schedule.*',
        'secure-files.*',
    ],

];
