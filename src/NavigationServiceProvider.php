<?php

declare(strict_types=1);

namespace SysMatter\Navigation;

use Illuminate\Support\ServiceProvider;
use SysMatter\Navigation\Commands\CompileIconsCommand;
use SysMatter\Navigation\Commands\ValidateNavigationCommand;

final class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/navigation.php',
            'navigation'
        );

        $this->app->singleton(NavigationManager::class, function ($app) {
            return new NavigationManager($app['config']['navigation']);
        });

        $this->app->alias(NavigationManager::class, 'navigation');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/navigation.php' => config_path('navigation.php'),
            ], 'navigation-config');

            $this->commands([
                CompileIconsCommand::class,
                ValidateNavigationCommand::class,
            ]);
        }
    }
}
