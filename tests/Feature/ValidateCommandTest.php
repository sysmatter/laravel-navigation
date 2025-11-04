<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Register test routes
    Route::name('home')->get('/', fn () => 'Home');
    Route::name('about')->get('/about', fn () => 'About');
    Route::name('users.index')->get('/users', fn () => 'Users');
    Route::name('users.show')->get('/users/{id}', fn () => 'User');
    Route::name('contact')->get('/contact', fn () => 'Contact');
});

it('validates navigation routes successfully', function () {
    config(['navigation.menus.main' => [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'About', 'route' => 'about'],
        ['type' => 'separator'],  // Separators should be skipped
        ['label' => 'Contact', 'route' => 'contact'],
    ]]);

    $this->artisan('navigation:validate')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('✓ All navigation routes are valid!')
        ->assertExitCode(0);
});

it('detects invalid routes', function () {
    config(['navigation.menus.main' => [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'Invalid', 'route' => 'nonexistent.route'],
        ['type' => 'separator'],  // Should be ignored
        ['label' => 'About', 'route' => 'about'],
    ]]);

    $this->artisan('navigation:validate')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('✗ Found 1 invalid route(s):')
        ->expectsOutput('  - Invalid (route: nonexistent.route)')
        ->assertExitCode(1);
});

it('validates nested routes', function () {
    config(['navigation.menus.main' => [
        [
            'label' => 'Users',
            'route' => 'users.index',
            'children' => [
                ['label' => 'All Users', 'route' => 'users.index'],
                ['type' => 'separator'],  // Should be ignored
                ['label' => 'View User', 'route' => 'users.show'],
            ],
        ],
    ]]);

    $this->artisan('navigation:validate')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('✓ All navigation routes are valid!')
        ->assertExitCode(0);
});

it('shows path to invalid route in error message', function () {
    config(['navigation.menus.main' => [
        [
            'label' => 'Users',
            'route' => 'users.index',
            'children' => [
                ['label' => 'All Users', 'route' => 'users.index'],
                ['type' => 'separator'],  // Should be ignored
                ['label' => 'Invalid Child', 'route' => 'invalid.route'],
            ],
        ],
    ]]);

    $this->artisan('navigation:validate')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('✗ Found 1 invalid route(s):')
        ->expectsOutputToContain('Users > Invalid Child')
        ->assertExitCode(1);
});

it('handles items without labels that are not separators', function () {
    config(['navigation.menus.main' => [
        ['label' => 'Home', 'route' => 'home'],
        ['route' => 'about'],  // Missing label - should be invalid
        ['type' => 'separator'],  // Should be OK
        ['label' => 'Contact', 'route' => 'contact'],
    ]]);

    $this->artisan('navigation:validate')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('✗ Found 1 invalid route(s):')
        ->expectsOutputToContain('Item at index 1 (missing label)')
        ->assertExitCode(1);
});

it('handles multiple navigation menus', function () {
    config([
        'navigation.menus.main' => [
            ['label' => 'Home', 'route' => 'home'],
            ['type' => 'separator'],
            ['label' => 'About', 'route' => 'about'],
        ],
        'navigation.menus.footer' => [
            ['label' => 'Contact', 'route' => 'contact'],
            ['type' => 'divider'],  // Another separator type
            ['label' => 'Users', 'route' => 'users.index'],
        ],
    ]);

    $this->artisan('navigation:validate')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('Validating navigation: footer')
        ->expectsOutput('✓ All navigation routes are valid!')
        ->assertExitCode(0);
});

it('validates specific navigation menu', function () {
    config([
        'navigation.menus.main' => [
            ['label' => 'Home', 'route' => 'home'],
        ],
        'navigation.menus.footer' => [
            ['label' => 'Invalid', 'route' => 'invalid.route'],
        ],
    ]);

    // Validating only 'main' should pass
    $this->artisan('navigation:validate main')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('✓ All navigation routes are valid!')
        ->assertExitCode(0);

    // Validating only 'footer' should fail
    $this->artisan('navigation:validate footer')
        ->expectsOutput('Validating navigation: footer')
        ->expectsOutput('✗ Found 1 invalid route(s):')
        ->assertExitCode(1);
});

it('handles deeply nested items with separators', function () {
    config(['navigation.menus.main' => [
        [
            'label' => 'Admin',
            'route' => 'home',
            'children' => [
                ['label' => 'Dashboard', 'route' => 'home'],
                ['type' => 'separator'],
                [
                    'label' => 'Users',
                    'route' => 'users.index',
                    'children' => [
                        ['label' => 'All Users', 'route' => 'users.index'],
                        ['type' => 'divider'],
                        ['label' => 'Invalid Nested', 'route' => 'invalid.nested.route'],
                    ],
                ],
            ],
        ],
    ]]);

    $this->artisan('navigation:validate')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('✗ Found 1 invalid route(s):')
        ->expectsOutputToContain('Admin > Users > Invalid Nested')
        ->assertExitCode(1);
});

it('skips all types of separators', function () {
    config(['navigation.menus.main' => [
        ['label' => 'Home', 'route' => 'home'],
        ['type' => 'separator'],
        ['type' => 'divider'],
        ['type' => 'spacer'],
        ['type' => 'break'],
        ['label' => 'About', 'route' => 'about'],
    ]]);

    $this->artisan('navigation:validate')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('✓ All navigation routes are valid!')
        ->assertExitCode(0);
});
