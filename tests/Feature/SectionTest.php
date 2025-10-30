<?php

use SysMatter\Navigation\IconCompiler;
use SysMatter\Navigation\Navigation;

beforeEach(function () {
    $this->iconCompiler = new IconCompiler();
});

test('sections can contain children', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'children' => [
                ['label' => 'Dashboard', 'route' => 'dashboard'],
                ['label' => 'Users', 'route' => 'users.index'],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['type'])->toBe('section')
        ->and($tree[0]['label'])->toBe('Admin')
        ->and($tree[0]['children'])->toHaveCount(2)
        ->and($tree[0]['children'][0]['label'])->toBe('Dashboard')
        ->and($tree[0]['children'][1]['label'])->toBe('Users');
});

test('section children have correct parent IDs', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'children' => [
                ['label' => 'Dashboard', 'route' => 'dashboard'],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0]['id'])->toBe('nav-test-0')
        ->and($tree[0]['children'][0]['id'])->toBe('nav-test-0-0');
});

test('sections with no visible children are excluded', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'children' => [
                ['label' => 'Hidden', 'route' => 'dashboard', 'visible' => false],
            ],
        ],
        ['label' => 'Visible', 'route' => 'users.index'],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    // Section should be excluded since it has no visible children
    expect($tree)->toHaveCount(1)
        ->and($tree[0]['label'])->toBe('Visible');
});

test('section visibility hides all children', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'visible' => false,
            'children' => [
                ['label' => 'Dashboard', 'route' => 'dashboard'],
                ['label' => 'Users', 'route' => 'users.index'],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toBeEmpty();
});

test('section can attribute hides all children when user lacks permission', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'can' => 'access-admin',
            'children' => [
                ['label' => 'Dashboard', 'route' => 'dashboard'],
                ['label' => 'Users', 'route' => 'users.index'],
            ],
        ],
    ];

    // User without permission
    $this->actingAs($this->createMockUser([]));

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toBeEmpty();
});

test('section can attribute shows children when user has permission', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'can' => 'access-admin',
            'children' => [
                ['label' => 'Dashboard', 'route' => 'dashboard'],
                ['label' => 'Users', 'route' => 'users.index'],
            ],
        ],
    ];

    // User with permission
    $this->actingAs($this->createMockUser(['access-admin']));

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['children'])->toHaveCount(2);
});

test('nested sections work correctly', function () {
    $items = [
        [
            'label' => 'Main',
            'type' => 'section',
            'children' => [
                ['label' => 'Dashboard', 'route' => 'dashboard'],
                [
                    'label' => 'Admin',
                    'type' => 'section',
                    'children' => [
                        ['label' => 'Users', 'route' => 'users.index'],
                        ['label' => 'Settings', 'route' => 'settings.index'],
                    ],
                ],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['children'])->toHaveCount(2)
        ->and($tree[0]['children'][1]['type'])->toBe('section')
        ->and($tree[0]['children'][1]['children'])->toHaveCount(2);
});

test('sections still skip items in breadcrumbs', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'children' => [
                ['label' => 'Dashboard', 'route' => 'dashboard'],
                ['label' => 'Users', 'route' => 'users.index'],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $breadcrumbs = $navigation->getBreadcrumbs('users.index');

    // Should only have Users, not Admin section
    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['label'])->toBe('Users');
});

test('sections work with separators', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'children' => [
                ['label' => 'Dashboard', 'route' => 'dashboard'],
                ['type' => 'separator'],
                ['label' => 'Users', 'route' => 'users.index'],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0]['children'])->toHaveCount(3)
        ->and($tree[0]['children'][0]['type'])->toBe('link')
        ->and($tree[0]['children'][1]['type'])->toBe('separator')
        ->and($tree[0]['children'][2]['type'])->toBe('link');
});

test('section children can have their own children', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'children' => [
                [
                    'label' => 'Users',
                    'route' => 'users.index',
                    'children' => [
                        ['label' => 'All Users', 'route' => 'users.index'],
                        ['label' => 'Roles', 'route' => 'users.roles.index'],
                    ],
                ],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0]['children'][0]['children'])->toHaveCount(2)
        ->and($tree[0]['children'][0]['children'][0]['label'])->toBe('All Users');
});

test('section children respect visibility', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'children' => [
                ['label' => 'Visible', 'route' => 'dashboard', 'visible' => true],
                ['label' => 'Hidden', 'route' => 'users.index', 'visible' => false],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['label'])->toBe('Visible');
});

test('section children respect can attribute', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'children' => [
                ['label' => 'Allowed', 'route' => 'dashboard', 'can' => 'view-dashboard'],
                ['label' => 'Forbidden', 'route' => 'users.index', 'can' => 'manage-users'],
            ],
        ],
    ];

    $this->actingAs($this->createMockUser(['view-dashboard']));

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['label'])->toBe('Allowed');
});

test('mixed sections and regular items work together', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        [
            'label' => 'Admin',
            'type' => 'section',
            'children' => [
                ['label' => 'Users', 'route' => 'users.index'],
                ['label' => 'Settings', 'route' => 'settings.index'],
            ],
        ],
        ['label' => 'Profile', 'route' => 'profile.edit'],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(3)
        ->and($tree[0]['type'])->toBe('link')
        ->and($tree[0]['label'])->toBe('Dashboard')
        ->and($tree[1]['type'])->toBe('section')
        ->and($tree[1]['label'])->toBe('Admin')
        ->and($tree[2]['type'])->toBe('link')
        ->and($tree[2]['label'])->toBe('Profile');
});

test('empty sections are still rendered', function () {
    $items = [
        [
            'label' => 'Empty Section',
            'type' => 'section',
            'children' => [],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['type'])->toBe('section')
        ->and($tree[0]['children'])->toBeEmpty();
});

test('sections without children array are rendered', function () {
    $items = [
        [
            'label' => 'Header Section',
            'type' => 'section',
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['type'])->toBe('section')
        ->and($tree[0]['children'])->toBeEmpty();
});

test('breadcrumbOnly items work within sections', function () {
    $items = [
        [
            'label' => 'Admin',
            'type' => 'section',
            'children' => [
                [
                    'label' => 'Users',
                    'route' => 'users.index',
                    'children' => [
                        ['label' => 'Edit User', 'route' => 'users.edit', 'breadcrumbOnly' => true],
                    ],
                ],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    // Edit User should not appear in nav
    expect($tree[0]['children'][0]['children'])->toBeEmpty();

    // But should appear in breadcrumbs
    $breadcrumbs = $navigation->getBreadcrumbs('users.edit');
    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users')
        ->and($breadcrumbs[1]['label'])->toBe('Edit User');
});
