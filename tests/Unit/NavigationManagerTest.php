<?php

use SysMatter\Navigation\Navigation;
use SysMatter\Navigation\NavigationManager;

beforeEach(function () {
    $this->config = $this->getTestConfig();
    $this->manager = new NavigationManager($this->config);
});

it('can get a navigation by name', function () {
    $navigation = $this->manager->get('main');

    expect($navigation)->toBeInstanceOf(Navigation::class);
});

it('returns empty array for non-existent navigation', function () {
    $navigation = $this->manager->get('non-existent');
    $tree = $navigation->toTree();

    expect($tree)->toBe([]);
});

it('can get all navigation names', function () {
    $names = $this->manager->getAllNavigations();

    expect($names)->toBe(['main', 'user_menu', 'footer']);
});

it('can generate breadcrumbs from route name', function () {
    $breadcrumbs = $this->manager->breadcrumbs('main', 'users.roles.index');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users')
        ->and($breadcrumbs[1]['label'])->toBe('Roles');
});
