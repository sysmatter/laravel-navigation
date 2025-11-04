<?php

declare(strict_types=1);

use SysMatter\Navigation\IconCompiler;
use SysMatter\Navigation\Navigation;

beforeEach(function () {
    $this->config = $this->getTestConfig();
    $this->iconCompiler = new IconCompiler();
});

it('generates a tree structure', function () {
    $navigation = new Navigation('main', $this->config['navigations']['main'], $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toBeArray()
        ->and($tree)->toHaveCount(3)
        ->and($tree[0])->toHaveKeys(['id', 'label', 'url', 'isActive', 'children', 'icon']);
});

it('includes node ids in tree', function () {
    $navigation = new Navigation('main', $this->config['navigations']['main'], $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0]['id'])->toBe('nav-main-0')
        ->and($tree[1]['id'])->toBe('nav-main-1')
        ->and($tree[1]['children'][0]['id'])->toBe('nav-main-1-0');
});

it('resolves route names to URLs', function () {
    $navigation = new Navigation('main', $this->config['navigations']['main'], $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0]['url'])->toBe(url('/'))
        ->and($tree[1]['url'])->toBe(url('/users'));
});

it('handles external URLs', function () {
    $navigation = new Navigation('footer', $this->config['navigations']['footer'], $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0]['url'])->toBe('https://docs.example.com');
});

it('includes method for action items', function () {
    $navigation = new Navigation('user_menu', $this->config['navigations']['user_menu'], $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[2])->toHaveKey('method')
        ->and($tree[2]['method'])->toBe('post');
});

it('does not include method for regular links', function () {
    $navigation = new Navigation('main', $this->config['navigations']['main'], $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0])->not->toHaveKey('method');
});

it('processes nested children', function () {
    $navigation = new Navigation('main', $this->config['navigations']['main'], $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[1]['children'])->toHaveCount(3)
        ->and($tree[1]['children'][0]['label'])->toBe('All Users')
        ->and($tree[1]['children'][1]['label'])->toBe('Roles');
});

it('resolves route parameters', function () {
    $items = [
        ['label' => 'User Profile', 'route' => 'users.show'],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree(['user' => 123]);

    expect($tree[0]['url'])->toBe(url('/users/123'));
});

it('includes custom attributes', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'custom' => 'value', 'badge' => '5'],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0])->toHaveKey('custom')
        ->and($tree[0]['custom'])->toBe('value')
        ->and($tree[0])->toHaveKey('badge')
        ->and($tree[0]['badge'])->toBe('5');
});

it('includes type field on all navigation items', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Users', 'route' => 'users.index'],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0])->toHaveKey('type')
        ->and($tree[0]['type'])->toBe('link')
        ->and($tree[1])->toHaveKey('type')
        ->and($tree[1]['type'])->toBe('link');
});

it('handles section type items', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['type' => 'section', 'label' => 'Management'],
        ['label' => 'Users', 'route' => 'users.index'],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(3)
        ->and($tree[0]['type'])->toBe('link')
        ->and($tree[1]['type'])->toBe('section')
        ->and($tree[1]['label'])->toBe('Management')
        ->and($tree[1])->not->toHaveKey('url')
        ->and($tree[1])->not->toHaveKey('isActive')
        ->and($tree[2]['type'])->toBe('link');
});

it('handles separator type items', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['type' => 'separator'],
        ['label' => 'Settings', 'route' => 'settings.index'],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(3)
        ->and($tree[0]['type'])->toBe('link')
        ->and($tree[1]['type'])->toBe('separator')
        ->and($tree[1])->not->toHaveKey('label')
        ->and($tree[1])->not->toHaveKey('url')
        ->and($tree[2]['type'])->toBe('link');
});

it('excludes sections and separators from breadcrumbs', function () {
    $items = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'children' => [
                ['type' => 'section', 'label' => 'Management'],
                ['label' => 'Users', 'route' => 'users.index'],
                ['type' => 'separator'],
                ['label' => 'Settings', 'route' => 'settings.index'],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $breadcrumbs = $navigation->getBreadcrumbs('settings.index');

    // Should only have Dashboard > Settings (skipping section and separator)
    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Dashboard')
        ->and($breadcrumbs[1]['label'])->toBe('Settings');
});

it('sets default type to link when not specified', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'], // No type specified
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0]['type'])->toBe('link');
});

it('excludes breadcrumbOnly items from navigation', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Edit User', 'route' => 'users.edit', 'breadcrumbOnly' => true],
        ['label' => 'Settings', 'route' => 'settings.index'],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['label'])->toBe('Dashboard')
        ->and($tree[1]['label'])->toBe('Settings');
});

it('includes breadcrumbOnly items in breadcrumbs', function () {
    $items = [
        [
            'label' => 'Users',
            'route' => 'users.index',
            'children' => [
                ['label' => 'Edit User', 'route' => 'users.edit', 'breadcrumbOnly' => true],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $breadcrumbs = $navigation->getBreadcrumbs('users.edit');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users')
        ->and($breadcrumbs[1]['label'])->toBe('Edit User');
});

it('excludes navOnly items from breadcrumbs', function () {
    $items = [
        [
            'label' => 'Admin',
            'route' => 'dashboard',
            'navOnly' => true,
            'children' => [
                ['label' => 'Users', 'route' => 'users.index'],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $breadcrumbs = $navigation->getBreadcrumbs('users.index');

    // Should only have "Users", not "Admin"
    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['label'])->toBe('Users');
});

it('includes navOnly items in navigation', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Admin Section', 'route' => 'dashboard', 'navOnly' => true],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(2)
        ->and($tree[1]['label'])->toBe('Admin Section');
});

it('does not include navOnly and breadcrumbOnly in output', function () {
    $items = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'navOnly' => true,
            'customAttr' => 'value',
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $tree = $navigation->toTree();

    expect($tree[0])->not->toHaveKey('navOnly')
        ->and($tree[0])->not->toHaveKey('breadcrumbOnly')
        ->and($tree[0])->toHaveKey('customAttr')
        ->and($tree[0]['customAttr'])->toBe('value');
});
