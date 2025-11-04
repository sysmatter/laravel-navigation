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
