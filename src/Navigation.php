<?php

namespace SysMatter\Navigation;

use Exception;
use Illuminate\Support\Str;

class Navigation
{
    protected string $name;
    /** @var array<int, array<string, mixed>> */
    protected array $items;
    protected IconCompiler $iconCompiler;

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(string $name, array $items, IconCompiler $iconCompiler)
    {
        $this->name = $name;
        $this->items = $items;
        $this->iconCompiler = $iconCompiler;
    }

    /**
     * @param array<string, mixed> $routeParams
     * @return array<int, array<string, mixed>>
     */
    public function toTree(array $routeParams = []): array
    {
        return $this->buildTree($this->items, $routeParams);
    }

    /**
     * @param array<string, mixed> $routeParams
     * @return array<int, array<string, mixed>>
     */
    public function getBreadcrumbs(string $currentRouteName, array $routeParams = []): array
    {
        $breadcrumbs = [];
        $this->findBreadcrumbPath($this->items, $currentRouteName, [], $breadcrumbs, $routeParams);

        return $breadcrumbs;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $routeParams
     * @return array<int, array<string, mixed>>
     */
    protected function buildTree(array $items, array $routeParams = [], ?string $parentId = null): array
    {
        $tree = [];
        $currentRoute = request()->route()?->getName();

        foreach ($items as $index => $item) {
            // Handle conditional visibility
            if (isset($item['visible'])) {
                if (is_callable($item['visible'])) {
                    if (!$item['visible']()) {
                        continue;
                    }
                } elseif (!$item['visible']) {
                    continue;
                }
            }

            // Handle gate/policy checks
            if (isset($item['can'])) {
                $user = auth()->user();

                if (!$user) {
                    continue;
                }

                if (is_array($item['can'])) {
                    [$ability, $arguments] = $item['can'];
                    if (!$user->can($ability, $arguments)) {
                        continue;
                    }
                } else {
                    if (!$user->can($item['can'])) {
                        continue;
                    }
                }
            }

            $id = $parentId ? "{$parentId}-{$index}" : "nav-{$this->name}-{$index}";

            $node = [
                'id' => $id,
                'label' => $item['label'],
                'isActive' => $this->isActive($item, $currentRoute),
                'children' => [],
            ];

            // Add URL
            if (isset($item['route'])) {
                $node['url'] = $this->resolveRoute($item['route'], $routeParams);
            } elseif (isset($item['url'])) {
                $node['url'] = $item['url'];
            }

            // Add method if present
            if (isset($item['method'])) {
                $node['method'] = $item['method'];
            }

            // Add icon if present
            if (isset($item['icon'])) {
                $node['icon'] = $this->iconCompiler->compile($item['icon']);
            }

            // Add any custom attributes (excluding internal ones)
            foreach ($item as $key => $value) {
                if (!in_array($key, ['label', 'route', 'url', 'method', 'icon', 'children', 'visible', 'can'])) {
                    $node[$key] = $value;
                }
            }

            // Process children
            if (isset($item['children']) && is_array($item['children'])) {
                $node['children'] = $this->buildTree($item['children'], $routeParams, $id);
            }

            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function isActive(array $item, ?string $currentRoute): bool
    {
        if (!$currentRoute) {
            return false;
        }

        if (isset($item['route'])) {
            // Check if current route matches
            if ($item['route'] === $currentRoute) {
                return true;
            }

            // Check if current route is a child route (e.g., users.index matches users.*)
            if (Str::startsWith($currentRoute, $item['route'] . '.')) {
                return true;
            }
        }

        // Check children
        if (isset($item['children'])) {
            foreach ($item['children'] as $child) {
                if ($this->isActive($child, $currentRoute)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function resolveRoute(string $routeName, array $params = []): string
    {
        try {
            return route($routeName, $params);
        } catch (Exception $e) {
            return '#';
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int, array<string, mixed>> $currentPath
     * @param array<int, array<string, mixed>> $breadcrumbs
     * @param array<string, mixed> $routeParams
     */
    protected function findBreadcrumbPath(
        array  $items,
        string $targetRoute,
        array  $currentPath,
        array  &$breadcrumbs,
        array  $routeParams = []
    ): bool {
        foreach ($items as $item) {
            $currentItem = [
                'label' => $item['label'],
            ];

            if (isset($item['route'])) {
                $currentItem['url'] = $this->resolveRoute($item['route'], $routeParams);
                $currentItem['route'] = $item['route'];
            } elseif (isset($item['url'])) {
                $currentItem['url'] = $item['url'];
            }

            $newPath = array_merge($currentPath, [$currentItem]);

            // Check if this is the target
            if (isset($item['route']) && $item['route'] === $targetRoute) {
                $breadcrumbs = $newPath;
                return true;
            }

            // Check children
            if (isset($item['children']) && is_array($item['children'])) {
                if ($this->findBreadcrumbPath($item['children'], $targetRoute, $newPath, $breadcrumbs, $routeParams)) {
                    return true;
                }
            }
        }

        return false;
    }
}
