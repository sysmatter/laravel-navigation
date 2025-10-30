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
        $currentRouteParams = request()->route()?->parameters() ?? [];

        foreach ($items as $index => $item) {
            // Handle conditional visibility first - if not visible, skip everything else
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

            // Get type, default to 'link'
            $type = $item['type'] ?? 'link';

            // Skip items marked as breadcrumb-only in navigation
            if (isset($item['breadcrumbOnly']) && $item['breadcrumbOnly']) {
                continue;
            }

            // Handle sections
            if ($type === 'section') {
                $section = [
                    'id' => $id,
                    'type' => 'section',
                    'label' => $item['label'],
                    'children' => [],
                ];

                // Process section children
                $hadChildren = isset($item['children']) && is_array($item['children']) && !empty($item['children']);
                if ($hadChildren) {
                    $section['children'] = $this->buildTree($item['children'], $routeParams, $id);
                }

                // Only exclude sections if they had children but all were filtered out
                if ($hadChildren && empty($section['children'])) {
                    continue;
                }

                $tree[] = $section;
                continue;
            }

            // Handle separators
            if ($type === 'separator') {
                $tree[] = [
                    'id' => $id,
                    'type' => 'separator',
                ];
                continue;
            }

            // DEFENSIVE: Skip items with dynamic labels or wildcards that aren't breadcrumbOnly
            $hasWildcardParams = isset($item['params']) && in_array('*', $item['params'], true);
            $hasDynamicLabel = isset($item['label']) && is_callable($item['label']);

            if ($hasWildcardParams || $hasDynamicLabel) {
                if (config('app.debug')) {
                    logger()->warning(
                        "Navigation item skipped: Items with wildcard params or dynamic labels should use 'breadcrumbOnly' => true",
                        [
                            'navigation' => $this->name,
                            'label' => $hasDynamicLabel ? '[closure]' : ($item['label'] ?? 'unknown'),
                            'route' => $item['route'] ?? null,
                        ]
                    );
                }
                continue;
            }

            $node = [
                'id' => $id,
                'type' => 'link',
                'label' => $item['label'],
                'isActive' => $this->isActive($item, $currentRoute, $currentRouteParams),
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
                if (!in_array($key, ['label', 'route', 'url', 'method', 'icon', 'children', 'visible', 'can', 'type', 'breadcrumbOnly', 'navOnly', 'params'])) {
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
     * @param array<string, mixed> $currentRouteParams
     */
    protected function isActive(array $item, ?string $currentRoute, array $currentRouteParams = []): bool
    {
        if (!$currentRoute) {
            return false;
        }

        if (isset($item['route'])) {
            // Check if current route matches with wildcard params
            if ($this->routeMatches($item['route'], $currentRoute, $item['params'] ?? null, $currentRouteParams)) {
                return true;
            }

            // Check if current route is a child route (e.g., users.index matches users.*)
            if (Str::startsWith($currentRoute, $item['route'] . '.')) {
                return true;
            }
        }

        // Check children (including breadcrumbOnly items for active state)
        if (isset($item['children'])) {
            foreach ($item['children'] as $child) {
                if ($this->isActive($child, $currentRoute, $currentRouteParams)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a route matches with wildcard parameter support
     *
     * @param array<string, mixed>|null $itemParams
     * @param array<string, mixed> $currentParams
     */
    protected function routeMatches(string $itemRoute, string $currentRoute, ?array $itemParams, array $currentParams): bool
    {
        if ($itemRoute !== $currentRoute) {
            return false;
        }

        // If no params specified, exact route match is enough
        if ($itemParams === null) {
            return true;
        }

        // Check if all wildcard params are present in current params
        foreach ($itemParams as $key => $value) {
            if ($value === '*') {
                // Wildcard - just check the param exists
                if (!isset($currentParams[$key])) {
                    return false;
                }
            } else {
                // Exact match required
                if (!isset($currentParams[$key]) || $currentParams[$key] != $value) {
                    return false;
                }
            }
        }

        return true;
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
        foreach ($items as $index => $item) {
            // Skip items marked as nav-only in breadcrumbs
            if (isset($item['navOnly']) && $item['navOnly']) {
                // Still check children
                if (isset($item['children']) && is_array($item['children'])) {
                    if ($this->findBreadcrumbPath($item['children'], $targetRoute, $currentPath, $breadcrumbs, $routeParams)) {
                        return true;
                    }
                }
                continue;
            }

            $type = $item['type'] ?? 'link';
            if (in_array($type, ['section', 'separator'])) {
                // Still check children if they exist
                if (isset($item['children']) && is_array($item['children'])) {
                    if ($this->findBreadcrumbPath($item['children'], $targetRoute, $currentPath, $breadcrumbs, $routeParams)) {
                        return true;
                    }
                }
                continue;
            }

            // Don't resolve label yet - wait until we know this is part of the path
            $currentItem = [
                'id' => $this->generateBreadcrumbId($currentPath, $index),
                'label' => $item['label'], // Keep the original label (string or closure)
            ];

            if (isset($item['route'])) {
                $currentItem['route'] = $item['route'];

                // For breadcrumb-only items with wildcards, use current route params
                if (isset($item['breadcrumbOnly']) && $item['breadcrumbOnly'] && isset($item['params'])) {
                    $resolvedParams = $this->resolveWildcardParams($item['params'], $routeParams);
                    $currentItem['url'] = $this->resolveRoute($item['route'], $resolvedParams);
                } else {
                    $currentItem['url'] = $this->resolveRoute($item['route'], $routeParams);
                }
            } elseif (isset($item['url'])) {
                $currentItem['url'] = $item['url'];
            }

            $newPath = array_merge($currentPath, [$currentItem]);

            // Check if this is the target (with wildcard support)
            if (isset($item['route'])) {
                if ($this->routeMatches($item['route'], $targetRoute, $item['params'] ?? null, $routeParams)) {
                    foreach ($newPath as &$pathItem) {
                        if (is_callable($pathItem['label'])) {
                            $pathItem['label'] = $this->resolveLabel($pathItem['label'], $routeParams);
                        }
                    }
                    unset($pathItem);
                    $breadcrumbs = $newPath;
                    return true;
                }
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

    /**
     * Resolve a label that might be a string or closure
     *
     * @param string|callable $label
     * @param array<string, mixed> $routeParams
     */
    protected function resolveLabel($label, array $routeParams): string
    {
        if (is_callable($label)) {
            // Pass route parameters to the closure
            // Extract model instances from route params for convenience
            $models = array_filter($routeParams, fn ($param) => is_object($param));

            // If there's only one model, pass it directly, otherwise pass all params
            if (count($models) === 1) {
                return $label(reset($models));
            }

            return $label($routeParams);
        }

        return $label;
    }

    /**
     * Resolve wildcard parameters with actual values
     *
     * @param array<string, mixed> $itemParams
     * @param array<string, mixed> $currentParams
     * @return array<string, mixed>
     */
    protected function resolveWildcardParams(array $itemParams, array $currentParams): array
    {
        $resolved = [];

        foreach ($itemParams as $key => $value) {
            if ($value === '*' && isset($currentParams[$key])) {
                $param = $currentParams[$key];

                // Convert model instances to their route keys
                if (is_object($param)) {
                    if (method_exists($param, 'getRouteKey')) {
                        $resolved[$key] = $param->getRouteKey();
                    } elseif (method_exists($param, '__toString')) {
                        $resolved[$key] = (string)$param;
                    } else {
                        $resolved[$key] = $param;
                    }
                } else {
                    $resolved[$key] = $param;
                }
            } elseif ($value !== '*') {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    /**
     * @param array<int, array<string, mixed>> $currentPath
     */
    protected function generateBreadcrumbId(array $currentPath, int $index): string
    {
        $depth = count($currentPath);
        return "breadcrumb-{$this->name}-{$depth}-{$index}";
    }
}
