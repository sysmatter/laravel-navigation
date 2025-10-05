<?php

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
