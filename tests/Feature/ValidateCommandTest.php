<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Register some test routes for validation
    Route::name('home')->get('/', fn () => 'Home');
    Route::name('about')->get('/about', fn () => 'About');
    Route::name('contact')->get('/contact', fn () => 'Contact');
    Route::name('users.index')->get('/users', fn () => 'Users');
    Route::name('users.show')->get('/users/{id}', fn () => 'User');
    Route::name('admin.dashboard')->get('/admin', fn () => 'Admin');
});

describe('ValidateNavigationCommand', function () {

    describe('separator handling', function () {

        it('validates navigation with separators without throwing errors', function () {
            config(['navigation.menus.main' => [
                ['label' => 'Home', 'route' => 'home'],
                ['type' => 'separator'],  // This should not cause "Undefined array key 'label'" error
                ['label' => 'About', 'route' => 'about'],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });

        it('handles multiple separator types', function () {
            config(['navigation.menus.main' => [
                ['label' => 'Home', 'route' => 'home'],
                ['type' => 'separator'],
                ['label' => 'About', 'route' => 'about'],
                ['type' => 'divider'],
                ['label' => 'Contact', 'route' => 'contact'],
                ['type' => 'spacer'],
                ['type' => 'break'],
                ['label' => 'Users', 'route' => 'users.index'],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });

        it('handles separators in nested navigation', function () {
            config(['navigation.menus.main' => [
                [
                    'label' => 'Admin',
                    'route' => 'admin.dashboard',
                    'children' => [
                        ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
                        ['type' => 'separator'],  // Separator in children
                        ['label' => 'Users', 'route' => 'users.index'],
                        ['type' => 'divider'],
                        ['label' => 'Settings', 'route' => 'home'],
                    ],
                ],
                ['type' => 'separator'],  // Separator at root level
                ['label' => 'Contact', 'route' => 'contact'],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });

        it('does not require labels for separators', function () {
            // This config would fail if separators required labels
            config(['navigation.menus.main' => [
                ['label' => 'Home', 'route' => 'home'],
                ['type' => 'separator'],  // No label - should be fine
                ['type' => 'divider'],    // No label - should be fine
                ['label' => 'About', 'route' => 'about'],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });

        it('still requires labels for non-separator items', function () {
            config(['navigation.menus.main' => [
                ['label' => 'Home', 'route' => 'home'],
                ['type' => 'separator'],  // OK without label
                ['route' => 'about'],     // NOT OK without label (not a separator)
                ['label' => 'Contact', 'route' => 'contact'],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutputToContain('Item at index 2 is missing \'label\' field')
                ->assertExitCode(1);
        });

        it('handles items without type that are missing labels', function () {
            config(['navigation.menus.main' => [
                ['label' => 'Home', 'route' => 'home'],
                ['url' => '/external'],  // No type, no label - should fail
                ['type' => 'separator'],  // Should pass
                ['label' => 'About'],     // No route/url but has label - should pass
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutputToContain('Item at index 1 is missing \'label\' field')
                ->assertExitCode(1);
        });
    });

    describe('basic validation', function () {

        it('validates simple navigation successfully', function () {
            config(['navigation.menus.main' => [
                ['label' => 'Home', 'route' => 'home'],
                ['label' => 'About', 'route' => 'about'],
                ['label' => 'Contact', 'route' => 'contact'],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });

        it('validates specific navigation menu', function () {
            config([
                'navigation.menus.header' => [
                    ['label' => 'Home', 'route' => 'home'],
                ],
                'navigation.menus.footer' => [
                    ['label' => 'About', 'route' => 'about'],
                ],
            ]);

            $this->artisan('navigation:validate header')
                ->expectsOutput('Validating navigation: header')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });

        it('fails when navigation does not exist', function () {
            config(['navigation.menus.main' => []]);

            $this->artisan('navigation:validate nonexistent')
                ->expectsOutput('No navigation configurations found.')
                ->assertExitCode(1);
        });
    });

    describe('nested navigation', function () {

        it('validates nested items correctly', function () {
            config(['navigation.menus.main' => [
                [
                    'label' => 'Users',
                    'route' => 'users.index',
                    'children' => [
                        ['label' => 'All Users', 'route' => 'users.index'],
                        ['label' => 'User Details', 'route' => 'users.show'],
                    ],
                ],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });

        it('reports errors in nested items with full path', function () {
            config(['navigation.menus.main' => [
                [
                    'label' => 'Admin',
                    'route' => 'admin.dashboard',
                    'children' => [
                        ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
                        ['route' => 'users.index'],  // Missing label in nested item
                    ],
                ],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutputToContain('Admin > Item at index 1 is missing \'label\' field')
                ->assertExitCode(1);
        });

        it('handles deeply nested structures with separators', function () {
            config(['navigation.menus.main' => [
                [
                    'label' => 'Level 1',
                    'children' => [
                        [
                            'label' => 'Level 2',
                            'children' => [
                                ['label' => 'Level 3 Item', 'route' => 'home'],
                                ['type' => 'separator'],
                                [
                                    'label' => 'Level 3 with children',
                                    'children' => [
                                        ['type' => 'divider'],
                                        ['label' => 'Level 4', 'route' => 'about'],
                                    ],
                                ],
                            ],
                        ],
                        ['type' => 'separator'],
                    ],
                ],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });
    });

    describe('edge cases', function () {

        it('handles empty navigation arrays', function () {
            config(['navigation.menus.main' => []]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });

        it('handles navigation with only separators', function () {
            config(['navigation.menus.main' => [
                ['type' => 'separator'],
                ['type' => 'divider'],
                ['type' => 'spacer'],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });

        it('handles mixed valid and invalid items', function () {
            config(['navigation.menus.main' => [
                ['label' => 'Valid 1', 'route' => 'home'],
                ['type' => 'separator'],
                ['route' => 'about'],  // Invalid - missing label
                ['type' => 'divider'],
                ['label' => 'Valid 2', 'route' => 'contact'],
                ['url' => '/test'],     // Invalid - missing label
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutputToContain('Item at index 2 is missing \'label\' field')
                ->expectsOutputToContain('Item at index 5 is missing \'label\' field')
                ->assertExitCode(1);
        });

        it('handles non-array items gracefully', function () {
            config(['navigation.menus.main' => [
                ['label' => 'Home', 'route' => 'home'],
                'string_item',  // Invalid - not an array
                ['type' => 'separator'],
                null,  // Invalid - null
                ['label' => 'About', 'route' => 'about'],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutputToContain('Item at index 1 must be an array')
                ->expectsOutputToContain('Item at index 3 must be an array')
                ->assertExitCode(1);
        });
    });

    describe('route validation', function () {

        it('detects invalid routes', function () {
            config(['navigation.menus.main' => [
                ['label' => 'Home', 'route' => 'home'],
                ['label' => 'Invalid', 'route' => 'nonexistent.route'],
                ['type' => 'separator'],
                ['label' => 'About', 'route' => 'about'],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutputToContain('Invalid (route: nonexistent.route)')
                ->assertExitCode(1);
        });

        it('allows items with URLs instead of routes', function () {
            config(['navigation.menus.main' => [
                ['label' => 'Home', 'route' => 'home'],
                ['label' => 'External', 'url' => 'https://example.com'],  // URL instead of route
                ['type' => 'separator'],
                ['label' => 'About', 'route' => 'about'],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });

        it('allows items with neither route nor URL', function () {
            config(['navigation.menus.main' => [
                ['label' => 'Dropdown', 'children' => [  // Parent with no route
                    ['label' => 'Child 1', 'route' => 'home'],
                    ['label' => 'Child 2', 'route' => 'about'],
                ]],
            ]]);

            $this->artisan('navigation:validate')
                ->expectsOutput('Validating navigation: main')
                ->expectsOutput('✓ All navigation routes are valid!')
                ->assertExitCode(0);
        });
    });
});
