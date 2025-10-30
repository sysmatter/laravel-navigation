<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Navigation Definitions
    |--------------------------------------------------------------------------
    |
    | Define your application's navigation structures here. Each navigation
    | can contain items with routes, external URLs, icons, and nested children.
    |
    */

    'navigations' => [
        'main' => [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'home',
            ],

            // Section container example
            [
                'label' => 'Management',
                'type' => 'section',
                'can' => 'view-management',  // Optional: hide entire section based on permission
                'children' => [
                    [
                        'label' => 'Users',
                        'route' => 'users.index',
                        'icon' => 'users',
                        'children' => [
                            ['label' => 'All Users', 'route' => 'users.index'],
                            ['label' => 'Roles', 'route' => 'users.roles.index'],
                            ['label' => 'Permissions', 'route' => 'users.permissions.index'],
                        ],
                    ],
                    [
                        'label' => 'Teams',
                        'route' => 'teams.index',
                        'icon' => 'users-2',
                    ],
                ],
            ],

            // Separator
            ['type' => 'separator'],

            [
                'label' => 'Settings',
                'route' => 'settings.index',
                'icon' => 'settings',
            ],
        ],

        'user_menu' => [
            ['label' => 'Profile', 'route' => 'profile.edit', 'icon' => 'user'],
            ['label' => 'Settings', 'route' => 'settings.index', 'icon' => 'settings'],

            ['type' => 'separator'],

            ['label' => 'Logout', 'route' => 'logout', 'method' => 'post', 'icon' => 'log-out'],
        ],

        'footer' => [
            ['label' => 'Documentation', 'url' => 'https://docs.example.com'],
            ['label' => 'Privacy Policy', 'route' => 'legal.privacy'],
            ['label' => 'Terms of Service', 'route' => 'legal.terms'],
        ],

        // Admin navigation with nested sections
        'admin' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'layout-dashboard'],

            [
                'label' => 'User Management',
                'type' => 'section',
                'can' => 'manage-users',
                'children' => [
                    ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
                    ['label' => 'Edit User', 'route' => 'admin.users.edit', 'breadcrumbOnly' => true],
                    ['label' => 'Roles', 'route' => 'admin.roles.index', 'icon' => 'shield'],
                    ['label' => 'Permissions', 'route' => 'admin.permissions.index', 'icon' => 'key'],
                ],
            ],

            [
                'label' => 'System',
                'type' => 'section',
                'can' => 'manage-system',
                'children' => [
                    ['label' => 'Features', 'route' => 'admin.features.index', 'icon' => 'flag'],
                    ['label' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'settings'],
                    ['label' => 'Audit Log', 'route' => 'admin.audit.index', 'icon' => 'file-text'],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Icon Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where compiled Lucide icons should be stored.
    |
    */

    'icons' => [
        'compiled_path' => storage_path('navigation/icons.php'),
    ],
];
