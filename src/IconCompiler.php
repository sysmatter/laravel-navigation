<?php

namespace SysMatter\Navigation;

use Exception;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IconCompiler
{
    /** @var array<string, string> */
    protected array $compiledIcons = [];
    protected bool $useCache = true;

    public function __construct()
    {
        $this->loadCompiledIcons();
    }

    public function compile(string $iconName): string
    {
        // Check if already compiled
        if (isset($this->compiledIcons[$iconName])) {
            return $this->compiledIcons[$iconName];
        }

        // Return icon name if not compiled (can be handled on frontend)
        return $iconName;
    }

    public function compileIcon(string $iconName): ?string
    {
        $url = "https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/{$iconName}.svg";

        try {
            $response = Http::get($url);

            if ($response->successful()) {
                $svg = $response->body();

                return preg_replace('/<svg/', '<svg data-slot="icon"', $svg, 1);
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * @param array<int, string> $iconNames
     * @return array<string, string>
     */
    public function compileAll(array $iconNames): array
    {
        $compiled = [];

        foreach ($iconNames as $iconName) {
            $svg = $this->compileIcon($iconName);
            if ($svg) {
                $compiled[$iconName] = $svg;
            }
        }

        return $compiled;
    }

    /**
     * @param array<string, string> $icons
     */
    public function saveCompiled(array $icons): void
    {
        $path = config('navigation.icons.compiled_path', storage_path('navigation/icons.php'));
        $directory = dirname($path);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }

        $content = "<?php\n\nreturn " . var_export($icons, true) . ";\n";
        file_put_contents($path, $content);

        $this->compiledIcons = $icons;
    }

    protected function loadCompiledIcons(): void
    {
        $path = config('navigation.icons.compiled_path', storage_path('navigation/icons.php'));

        if (file_exists($path)) {
            $this->compiledIcons = require $path;
        }
    }

    /**
     * @param array<string, mixed> $config
     * @return array<int, string>
     */
    public function extractIconsFromConfig(array $config): array
    {
        $icons = [];

        foreach ($config as $navigation) {
            $this->extractIconsRecursive($navigation, $icons);
        }

        return array_unique($icons);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int, mixed> $icons
     */
    protected function extractIconsRecursive(array $items, array &$icons): void
    {
        foreach ($items as $item) {
            if (isset($item['icon'])) {
                $icons[] = $item['icon'];
            }

            if (isset($item['children']) && is_array($item['children'])) {
                $this->extractIconsRecursive($item['children'], $icons);
            }
        }
    }
}
