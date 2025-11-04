<?php

declare(strict_types=1);

namespace SysMatter\Navigation\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

final class ValidateNavigationCommand extends Command
{
    protected $signature = 'navigation:validate {name? : The name of the navigation to validate}';

    protected $description = 'Validate navigation configuration and routes';

    /**
     * @var array<int, string>
     */
    protected array $invalidRoutes = [];

    public function handle(): int
    {
        /** @var string $navigationName */
        $navigationName = $this->argument('name');

        if ($navigationName) {
            $navigations = [$navigationName => config("navigation.menus.{$navigationName}")];
        } else {
            $navigations = config('navigation.menus', []);
        }

        if (empty($navigations)) {
            $this->error('No navigation configurations found.');

            return self::FAILURE;
        }

        $this->invalidRoutes = [];

        foreach ($navigations as $name => $items) {
            $this->info("Validating navigation: {$name}");

            if (! is_array($items)) {
                $this->error("  ✗ Navigation '{$name}' must be an array.");

                return self::FAILURE;
            }

            $this->validateItems($items);
        }

        if (! empty($this->invalidRoutes)) {
            $this->error('✗ Found '.count($this->invalidRoutes).' invalid route(s):');
            foreach ($this->invalidRoutes as $route) {
                $this->error("  - {$route}");
            }

            return self::FAILURE;
        }

        $this->info('✓ All navigation routes are valid!');

        return self::SUCCESS;
    }

    /**
     * Validate navigation items recursively.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, string>  $parentPath
     */
    protected function validateItems(array $items, array $parentPath = []): void
    {
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            // Check item type - separators and dividers don't need labels or routes
            $itemType = $item['type'] ?? 'link';

            // Skip validation for separator types
            if (in_array($itemType, ['separator', 'divider', 'spacer', 'break'])) {
                continue;
            }

            // Build current path for error messages
            $currentPath = $parentPath;
            if (isset($item['label'])) {
                $currentPath[] = $item['label'];
            } else {
                // If no label, item is invalid for non-separator types
                $pathString = ! empty($parentPath) ? implode(' > ', $parentPath).' > ' : '';
                $this->invalidRoutes[] = $pathString."Item at index {$index} (missing label)";

                continue;
            }

            // Validate route if specified
            if (isset($item['route'])) {
                if (! Route::has($item['route'])) {
                    $this->invalidRoutes[] = implode(' > ', $currentPath)." (route: {$item['route']})";
                }
            }

            // Validate children recursively
            if (isset($item['children']) && is_array($item['children'])) {
                $this->validateItems($item['children'], $currentPath);
            }
        }
    }
}
