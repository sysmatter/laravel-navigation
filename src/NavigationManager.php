<?php

namespace SysMatter\Navigation;

class NavigationManager
{
    /** @var array<string, mixed> */
    protected array $config;
    protected IconCompiler $iconCompiler;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->iconCompiler = new IconCompiler();
    }

    public function get(string $name): Navigation
    {
        $items = $this->config['navigations'][$name] ?? [];

        return new Navigation($name, $items, $this->iconCompiler);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function breadcrumbs(?string $name = null, ?string $routeName = null): array
    {
        $routeName = $routeName ?? request()->route()?->getName();

        if (!$routeName) {
            return [];
        }

        // If specific navigation provided, search only that one
        if ($name !== null) {
            $navigation = $this->get($name);
            return $navigation->getBreadcrumbs($routeName);
        }

        // Otherwise, search all navigations
        foreach ($this->config['navigations'] ?? [] as $navName => $items) {
            $navigation = $this->get($navName);
            $breadcrumbs = $navigation->getBreadcrumbs($routeName);

            if (!empty($breadcrumbs)) {
                return $breadcrumbs;
            }
        }

        return [];
    }

    /**
     * @return list<int|string>
     */
    public function getAllNavigations(): array
    {
        return array_keys($this->config['navigations'] ?? []);
    }
}
