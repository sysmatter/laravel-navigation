<?php

declare(strict_types=1);

it('validates navigation routes successfully', function () {
    config(['navigation' => $this->getTestConfig()]);

    $this->artisan('navigation:validate')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('✓ All navigation routes are valid!')
        ->assertExitCode(0);
});

it('detects invalid routes', function () {
    $config = $this->getTestConfig();
    $config['navigations']['main'][] = [
        'label' => 'Invalid',
        'route' => 'non.existent.route',
    ];

    config(['navigation' => $config]);

    $this->artisan('navigation:validate')
        ->expectsOutput('✗ Found 1 invalid route(s):')
        ->assertExitCode(1);
});

it('validates nested routes', function () {
    $config = $this->getTestConfig();
    $config['navigations']['main'][1]['children'][] = [
        'label' => 'Invalid Child',
        'route' => 'invalid.nested.route',
    ];

    config(['navigation' => $config]);

    $this->artisan('navigation:validate')
        ->assertExitCode(1);
});

it('shows path to invalid route in error message', function () {
    $config = $this->getTestConfig();
    $config['navigations']['main'][1]['children'][] = [
        'label' => 'Invalid Child',
        'route' => 'invalid.route',
    ];

    config(['navigation' => $config]);

    $this->artisan('navigation:validate')
        ->expectsOutputToContain('Users > Invalid Child')
        ->assertExitCode(1);
});

it('handles navigation items with separators without throwing errors', function () {
    // Create a minimal config with only separators and items without routes
    // This tests that separators don't cause "Undefined array key 'label'" errors
    $config = [
        'navigations' => [
            'main' => [
                ['label' => 'Home'],  // No route, so won't fail validation
                ['type' => 'separator'],  // This should not cause PHP error
                ['label' => 'About'],  // No route
                ['type' => 'divider'],    // Another separator type
                [
                    'label' => 'Dropdown',
                    'children' => [
                        ['label' => 'Child 1'],
                        ['type' => 'separator'],  // Separator in nested items
                        ['label' => 'Child 2'],
                        ['type' => 'spacer'],
                        ['type' => 'break'],
                    ],
                ],
            ],
        ],
    ];

    config(['navigation' => $config]);

    // The main goal: command completes without PHP errors from accessing missing 'label' key
    $this->artisan('navigation:validate')
        ->expectsOutput('Validating navigation: main')
        ->expectsOutput('✓ All navigation routes are valid!')
        ->assertExitCode(0);
});
