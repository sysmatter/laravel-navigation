<?php

declare(strict_types=1);

namespace SysMatter\Navigation\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

final class ValidateNavigationCommand extends Command
{
    protected $signature = 'navigation:validate {name? : The name of the navigation to validate}';

    protected $description = 'Validate navigation configuration';

    public function handle(): int
    {
        /** @var string $navigationName */
        $navigationName = $this->argument('name');

        if ($navigationName) {
            $items = config("navigation.menus.{$navigationName}");
            if ($items === null) {
                $this->error('No navigation configurations found.');

                return self::FAILURE;
            }
            $navigations = [$navigationName => $items];
        } else {
            $navigations = config('navigation.menus', []);
        }

        if (empty($navigations)) {
            $this->error('No navigation configurations found.');

            return self::FAILURE;
        }

        $hasErrors = false;

        foreach ($navigations as $name => $items) {
            $this->info("Validating navigation: {$name}");

            if (! is_array($items)) {
                $this->error("  ✗ Navigation '{$name}' must be an array.");
                $hasErrors = true;

                continue;
            }

            $errors = $this->validateItems($items);
            if (! empty($errors)) {
                $hasErrors = true;
                foreach ($errors as $error) {
                    $this->error("  ✗ {$error}");
                }
            }
        }

        if ($hasErrors) {
            $this->error('Navigation validation failed.');

            return self::FAILURE;
        }

        $this->info('✓ All navigation routes are valid!');

        return self::SUCCESS;
    }

    /**
     * Validate navigation items recursively.
     *
     * @param  array<int, mixed>  $items
     * @param  array<int, string>  $parentPath
     * @return array<int, string>
     */
    protected function validateItems(array $items, array $parentPath = []): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $errors[] = "Item at index {$index} must be an array.";

                continue;
            }

            // Check item type - separators and dividers don't need labels
            $itemType = $item['type'] ?? 'link';

            // Skip validation for separator types
            if (in_array($itemType, ['separator', 'divider', 'spacer', 'break'], true)) {
                continue;
            }

            // For non-separator items, validate required fields
            if (! isset($item['label'])) {
                $pathString = ! empty($parentPath) ? implode(' > ', $parentPath).' > ' : '';
                $errors[] = $pathString."Item at index {$index} is missing 'label' field.";

                continue;
            }

            // Build current path for nested items
            $currentPath = array_merge($parentPath, [$item['label']]);

            // Validate route if specified
            if (isset($item['route']) && ! Route::has($item['route'])) {
                $pathString = implode(' > ', $currentPath);
                $errors[] = "{$pathString} (route: {$item['route']})";
            }

            // Validate children recursively
            if (isset($item['children']) && is_array($item['children'])) {
                $childErrors = $this->validateItems($item['children'], $currentPath);
                $errors = array_merge($errors, $childErrors);
            }
        }

        return $errors;
    }
}
