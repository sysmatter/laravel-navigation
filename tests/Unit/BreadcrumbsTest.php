<?php

use SysMatter\Navigation\IconCompiler;
use SysMatter\Navigation\Navigation;

beforeEach(function () {
    $this->config = $this->getTestConfig();
    $this->iconCompiler = new IconCompiler();
});

it('generates breadcrumbs for top-level route', function () {
    $navigation = new Navigation('main', $this->config['navigations']['main'], $this->iconCompiler);
    $breadcrumbs = $navigation->getBreadcrumbs('dashboard');

    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['label'])->toBe('Dashboard')
        ->and($breadcrumbs[0]['route'])->toBe('dashboard');
});

it('generates breadcrumbs for nested route', function () {
    $navigation = new Navigation('main', $this->config['navigations']['main'], $this->iconCompiler);
    $breadcrumbs = $navigation->getBreadcrumbs('users.roles.index');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users')
        ->and($breadcrumbs[0]['route'])->toBe('users.index')
        ->and($breadcrumbs[1]['label'])->toBe('Roles')
        ->and($breadcrumbs[1]['route'])->toBe('users.roles.index');
});

it('generates breadcrumbs with route parameters', function () {
    $items = [
        [
            'label' => 'Users',
            'route' => 'users.index',
            'children' => [
                ['label' => 'User Profile', 'route' => 'users.show'],
            ],
        ],
    ];

    $navigation = new Navigation('test', $items, $this->iconCompiler);
    $breadcrumbs = $navigation->getBreadcrumbs('users.show', ['user' => 123]);

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[1]['url'])->toBe(url('/users/123'));
});

it('returns empty array when route not found', function () {
    $navigation = new Navigation('main', $this->config['navigations']['main'], $this->iconCompiler);
    $breadcrumbs = $navigation->getBreadcrumbs('non.existent.route');

    expect($breadcrumbs)->toBe([]);
});

it('includes URLs in breadcrumbs', function () {
    $navigation = new Navigation('main', $this->config['navigations']['main'], $this->iconCompiler);
    $breadcrumbs = $navigation->getBreadcrumbs('users.roles.index');

    expect($breadcrumbs[0])->toHaveKey('url')
        ->and($breadcrumbs[0]['url'])->toBe(url('/users'))
        ->and($breadcrumbs[1]['url'])->toBe(url('/users/roles'));
});
