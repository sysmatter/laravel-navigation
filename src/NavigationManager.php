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
    public function breadcrumbs(string $name, ?string $routeName = null): array
    {
        $navigation = $this->get($name);
        $routeName = $routeName ?? request()->route()?->getName();

        if (!$routeName) {
            return [];
        }

        return $navigation->getBreadcrumbs($routeName);
    }

    /**
     * @return list<int|string>
     */
    public function getAllNavigations(): array
    {
        return array_keys($this->config['navigations'] ?? []);
    }
}
