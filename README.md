# Laravel Navigation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sysmatter/laravel-navigation.svg?style=flat-square)](https://packagist.org/packages/sysmatter/laravel-navigation)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/sysmatter/laravel-navigation/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/sysmatter/laravel-navigation/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/sysmatter/laravel-navigation/code-style.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/sysmatter/laravel-navigation/actions?query=workflow%3A"code+style"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/sysmatter/laravel-navigation.svg?style=flat-square)](https://packagist.org/packages/sysmatter/laravel-navigation)

A powerful, flexible navigation management package for Laravel applications. Define multiple navigation structures with
breadcrumbs, active state detection, and pre-compiled icons - perfect for React, Inertia.js, and traditional Blade
applications.

## Features

- 🗺️ **Multiple Navigations** - Define unlimited navigation structures (main nav, footer, sidebars, etc.)
- 🔗 **Route-Based** - Use Laravel route names with full IDE autocomplete support
- 🍞 **Breadcrumb Generation** - Automatically generate breadcrumbs from your navigation structure
- 🌳 **Tree Export** - Export navigation as nested JSON for frontend frameworks
- ✨ **Active State Detection** - Smart detection of active menu items and their parents
- 🎨 **Pre-compiled Icons** - Compile Lucide icons to SVG strings for optimal performance
- 🔘 **Action Support** - Define POST/DELETE actions (logout, form submissions, etc.)
- 🌐 **External URLs** - Mix internal routes with external links
- ✅ **Validation** - Artisan command to validate all route references
- 🧪 **Fully Tested** - Comprehensive Pest test suite

## Installation

Install via Composer:

```bash
composer require sysmatter/laravel-navigation
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=navigation-config
```

## Configuration

Define your navigations in `config/navigation.php`:

```php
return [
    'navigations' => [
        'main' => [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'home',
            ],
            [
                'label' => 'Users',
                'route' => 'users.index',
                'icon' => 'users',
                'can' => 'view-users',  // Only show if user has permission
                'children' => [
                    ['label' => 'All Users', 'route' => 'users.index'],
                    ['label' => 'Roles', 'route' => 'users.roles.index', 'can' => 'manage-roles'],
                    ['label' => 'Permissions', 'route' => 'users.permissions.index', 'can' => 'manage-permissions'],
                ],
            ],
            [
                'label' => 'Settings',
                'route' => 'settings.index',
                'icon' => 'settings',
                'visible' => fn() => auth()->user()?->isAdmin(),  // Dynamic visibility
            ],
        ],

        'user_menu' => [
            ['label' => 'Profile', 'route' => 'profile.edit', 'icon' => 'user'],
            ['label' => 'Settings', 'route' => 'settings.index', 'icon' => 'settings'],
            ['label' => 'Logout', 'route' => 'logout', 'method' => 'post', 'icon' => 'log-out'],
        ],

        'footer' => [
            ['label' => 'Documentation', 'url' => 'https://docs.example.com'],
            ['label' => 'Privacy Policy', 'route' => 'legal.privacy'],
            ['label' => 'Terms of Service', 'route' => 'legal.terms'],
        ],
    ],

    'icons' => [
        'compiled_path' => storage_path('navigation/icons.php'),
    ],
];
```

## Usage

### Basic Usage

Get a navigation tree for your frontend:

```php
use SysMatter\Navigation\Facades\Navigation;

// Get navigation tree
$mainNav = Navigation::get('main')->toTree();

// Pass to Inertia
return inertia('Dashboard', [
    'navigation' => $mainNav,
]);

// Or return as JSON
return response()->json([
    'navigation' => $mainNav,
]);
```

### With Route Parameters

For routes that require parameters:

```php
// Route: /users/{user}/posts
$navigation = Navigation::get('sidebar')->toTree([
    'user' => $user->id,
]);
```

### Wildcard Parameters

#### Overview

Wildcard parameters allow you to include CRUD detail/edit pages in breadcrumbs and active states without cluttering your
navigation menu. This is particularly useful for pages like `/users/123/edit` that should show breadcrumbs and mark
parent items as active, but shouldn't appear in the navigation itself.

#### Basic Usage

##### Breadcrumb-Only Items

Use `breadcrumbOnly: true` to hide items from navigation while keeping them in breadcrumbs:

```php
'navigations' => [
    'main' => [
        [
            'label' => 'Users',
            'route' => 'users.index',
            'icon' => 'users',
            'children' => [
                [
                    'label' => 'Edit User',
                    'route' => 'users.edit',
                    'breadcrumbOnly' => true,
                    'params' => ['user' => '*'],
                ],
            ],
        ],
    ],
],
```

When visiting `/users/5/edit`:

- **Navigation**: Only shows "Users" link
- **Breadcrumbs**: Shows "Users > Edit User"
- **Active State**: "Users" is marked as active

#### Wildcard Syntax

##### Single Wildcard Parameter

The `'*'` wildcard matches any parameter value:

```php
[
    'label' => 'View Order',
    'route' => 'orders.show',
    'breadcrumbOnly' => true,
    'params' => ['order' => '*'],
]
```

Matches:

- `/orders/1` ✓
- `/orders/999` ✓
- `/orders/abc-123` ✓

##### Multiple Wildcard Parameters

```php
[
    'label' => 'Edit Team Member',
    'route' => 'organizations.teams.members.edit',
    'breadcrumbOnly' => true,
    'params' => [
        'organization' => '*',
        'team' => '*',
        'member' => '*',
    ],
]
```

##### Mixed Wildcard and Exact Parameters

Combine wildcards with exact values to match specific scenarios:

```php
[
    'label' => 'Edit Admin User',
    'route' => 'organizations.users.edit',
    'breadcrumbOnly' => true,
    'params' => [
        'organization' => 5, // Only match organization 5
        'user' => '*',       // Any user
    ],
]
```

Matches:

- `/organizations/5/users/10/edit` ✓
- `/organizations/5/users/99/edit` ✓
- `/organizations/3/users/10/edit` ✗

#### Dynamic Labels

##### Single Model Instance

Use closures to create dynamic labels based on route model binding:

```php
[
    'label' => fn($user) => "Edit: {$user->name}",
    'route' => 'users.edit',
    'breadcrumbOnly' => true,
    'params' => ['user' => '*'],
]
```

Result: "Users > Edit: John Doe"

##### Multiple Models

When multiple models are present, the closure receives all parameters:

```php
[
    'label' => fn($params) => "Edit {$params['user']->name} in {$params['organization']->name}",
    'route' => 'organizations.users.edit',
    'breadcrumbOnly' => true,
    'params' => [
        'organization' => '*',
        'user' => '*',
    ],
]
```

Result: "Organizations > Users > Edit Jane Smith in Acme Corp"

##### Static Labels

You can also use simple strings for labels:

```php
[
    'label' => 'Edit User',
    'route' => 'users.edit',
    'breadcrumbOnly' => true,
    'params' => ['user' => '*'],
]
```

Result: "Users > Edit User"

#### Active State

##### Automatic Bubbling

Active state automatically bubbles up through the navigation hierarchy:

```php
[
    'label' => 'Admin',
    'route' => 'admin.dashboard',
    'children' => [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => 'Edit User',
                    'route' => 'admin.users.edit',
                    'breadcrumbOnly' => true,
                    'params' => ['user' => '*'],
                ],
            ],
        ],
    ],
]
```

When on `/admin/users/5/edit`:

- "Admin" is active ✓
- "Users" is active ✓
- "Edit User" is in breadcrumbs but not in nav

#### Common Patterns

##### CRUD Resources

```php
[
    'label' => 'Products',
    'route' => 'products.index',
    'icon' => 'package',
    'children' => [
        [
            'label' => 'Create Product',
            'route' => 'products.create',
            'breadcrumbOnly' => true,
        ],
        [
            'label' => fn($product) => $product->name,
            'route' => 'products.show',
            'breadcrumbOnly' => true,
            'params' => ['product' => '*'],
        ],
        [
            'label' => fn($product) => "Edit: {$product->name}",
            'route' => 'products.edit',
            'breadcrumbOnly' => true,
            'params' => ['product' => '*'],
        ],
    ],
]
```

##### Nested Resources

```php
[
    'label' => 'Organizations',
    'route' => 'organizations.index',
    'children' => [
        [
            'label' => fn($org) => $org->name,
            'route' => 'organizations.show',
            'breadcrumbOnly' => true,
            'params' => ['organization' => '*'],
            'children' => [
                [
                    'label' => 'Team Members',
                    'route' => 'organizations.members.index',
                    'params' => ['organization' => '*'],
                ],
                [
                    'label' => fn($params) => "Edit {$params['member']->name}",
                    'route' => 'organizations.members.edit',
                    'breadcrumbOnly' => true,
                    'params' => [
                        'organization' => '*',
                        'member' => '*',
                    ],
                ],
            ],
        ],
    ],
]
```

#### Parameters Without Wildcards

You can also pass regular parameters for URL generation:

```php
// Generate navigation with specific params
$tree = Navigation::get('main')->toTree(['filter' => 'active']);

// Generate breadcrumbs with params
$breadcrumbs = Navigation::breadcrumbs('main', 'users.edit', ['user' => 5]);
```

#### Tips

1. **Model Requirements**: When using dynamic labels with models, ensure your models implement `getRouteKey()` or
   `__toString()` for proper URL generation.

2. **Performance**: Wildcard matching happens at runtime, so it's performant even with many breadcrumb-only items.

3. **Specificity**: More specific routes should come before more general ones in your navigation config.

4. **Navigation vs Breadcrumbs**: Use `breadcrumbOnly` for detail/edit pages, and `navOnly` (if needed) for items that
   should only appear in navigation but not breadcrumbs.

#### Frontend Integration

When building your React/TypeScript components, breadcrumb-only items won't appear in the navigation tree but will be
present in breadcrumbs:

```typescript
// Navigation returns only visible items
const navTree = await fetch('/api/navigation/main');
// Items with breadcrumbOnly are excluded

// Breadcrumbs include breadcrumb-only items
const breadcrumbs = await fetch('/api/breadcrumbs');
// [
//   { label: 'Users', url: '/users' },
//   { label: 'Edit: John Doe', url: '/users/5/edit' }
// ]
```

### Breadcrumbs

Generate breadcrumbs from the current route:

```php
// Auto-detect current route
$breadcrumbs = Navigation::breadcrumbs();

// Specify nav tree to find current route in
$breadcrumbs = Navigation::breadcrumbs('main');

// Or specify a route
$breadcrumbs = Navigation::breadcrumbs('main', 'users.show');

// With parameters
$breadcrumbs = Navigation::breadcrumbs('main', 'users.show', [
    'user' => $user->id,
]);
```

### Output Format

The `toTree()` method returns an array structure perfect for frontend consumption:

```php
[
    [
        'id' => 'nav-main-0',
        'label' => 'Dashboard',
        'url' => 'http://localhost/dashboard',
        'isActive' => true,
        'icon' => '<svg>...</svg>',  // Compiled SVG (if icons compiled)
        'children' => [],
    ],
    [
        'id' => 'nav-main-1',
        'label' => 'Users',
        'url' => 'http://localhost/users',
        'isActive' => false,
        'icon' => '<svg>...</svg>',
        'children' => [
            [
                'id' => 'nav-main-1-0',
                'label' => 'All Users',
                'url' => 'http://localhost/users',
                'isActive' => false,
                'children' => [],
            ],
            // ...
        ],
    ],
    [
        'id' => 'nav-main-2',
        'label' => 'Logout',
        'url' => 'http://localhost/logout',
        'method' => 'post',  // Only present when specified
        'isActive' => false,
        'icon' => '<svg>...</svg>',
        'children' => [],
    ],
]
```

## Frontend Integration

### React + Inertia.js

```tsx
import {Link} from '@inertiajs/react';

interface NavigationItem {
    id: string;
    type: 'link' | 'section' | 'separator';
    label?: string;
    url?: string;
    method?: string;
    isActive?: boolean;
    icon?: string;
    children?: NavigationItem[];
}

export default function Navigation({items}: { items: NavigationItem[] }) {
    return (
        <nav>
            {items.map((item) => (
                <div key={item.id}>
                    {item.method ? (
                        <Link
                            href={item.url}
                            method={item.method}
                            as="button"
                            className={item.isActive ? 'active' : ''}
                        >
                            {item.icon && (
                                <span dangerouslySetInnerHTML={{__html: item.icon}}/>
                            )}
                            {item.label}
                        </Link>
                    ) : (
                        <Link href={item.url} className={item.isActive ? 'active' : ''}>
                            {item.icon && (
                                <span dangerouslySetInnerHTML={{__html: item.icon}}/>
                            )}
                            {item.label}
                        </Link>
                    )}

                    {item.children.length > 0 && (
                        <Navigation items={item.children}/>
                    )}
                </div>
            ))}
        </nav>
    );
}
```

### TypeScript + React

```tsx
// Pass from Laravel
const Page = ({navigation}: { navigation: NavigationItem[] }) => {
    return <Navigation items={navigation}/>;
};
```

### Breadcrumbs Component

```tsx
import {Link} from '@inertiajs/react';

interface Breadcrumb {
    label: string;
    url: string;
    route: string;
}

export default function Breadcrumbs({items}: { items: Breadcrumb[] }) {
    return (
        <nav aria-label="Breadcrumb">
            <ol className="flex space-x-2">
                {items.map((item, index) => (
                    <li key={item.route} className="flex items-center">
                        {index > 0 && <span className="mx-2">/</span>}
                        {index === items.length - 1 ? (
                            <span className="text-gray-500">{item.label}</span>
                        ) : (
                            <Link href={item.url} className="text-blue-600 hover:underline">
                                {item.label}
                            </Link>
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}
```

## Icon Compilation

For optimal performance, compile Lucide icons to SVG strings instead of using the DynamicIcon component:

```bash
php artisan navigation:compile-icons
```

This command:

1. Extracts all icon names from your navigation config
2. Downloads SVG files from the Lucide CDN
3. Saves them as PHP arrays in `storage/navigation/icons.php`
4. Automatically includes them in your navigation output

**Benefits:**

- ✅ No runtime overhead
- ✅ No client-side icon loading
- ✅ Smaller bundle size
- ✅ Faster page loads

Add to your deployment process:

```bash
php artisan navigation:compile-icons
```

## Route Validation

Ensure all route names in your navigation config exist:

```bash
php artisan navigation:validate
```

Output:

```
Validating navigation: main
Validating navigation: user_menu
Validating navigation: footer
✓ All navigation routes are valid!
```

Or if there are errors:

```
Validating navigation: main
✗ Found 1 invalid route(s):
  - main: Route 'users.invalid' not found (at: Users > Invalid Link)
```

**Add to CI/CD:**

```yaml
# .github/workflows/tests.yml
-   name: Validate Navigation
    run: php artisan navigation:validate
```

## Advanced Features

### Custom Attributes

Add any custom data to navigation items:

```php
'navigations' => [
    'main' => [
        [
            'label' => 'Notifications',
            'route' => 'notifications.index',
            'badge' => '5',           // Custom attribute
            'badgeColor' => 'red',    // Custom attribute
            'requiresPro' => true,    // Custom attribute
        ],
    ],
],
```

These will be included in the output:

```php
[
    'label' => 'Notifications',
    'url' => 'http://localhost/notifications',
    'badge' => '5',
    'badgeColor' => 'red',
    'requiresPro' => true,
    // ...
]
```

### Conditional Visibility

Control which navigation items are visible based on permissions, authentication, or custom logic.

#### Using `visible` Attribute

Show/hide items with boolean values or callables:

```php
'navigations' => [
    'main' => [
        // Static boolean
        [
            'label' => 'Beta Features',
            'route' => 'beta.index',
            'visible' => config('app.beta_enabled'),
        ],
        
        // Dynamic callable
        [
            'label' => 'Admin Panel',
            'route' => 'admin.index',
            'visible' => fn() => auth()->user()?->isAdmin(),
        ],
        
        // Complex logic
        [
            'label' => 'Premium Features',
            'route' => 'premium.index',
            'visible' => fn() => auth()->check() && auth()->user()->hasActiveSubscription(),
        ],
    ],
],
```

#### Using `can` Attribute (Authorization)

Leverage Laravel's authorization gates and policies:

```php
'navigations' => [
    'main' => [
        // Simple gate check
        [
            'label' => 'Users',
            'route' => 'users.index',
            'can' => 'view-users',
        ],
        
        // Policy with model
        [
            'label' => 'Edit Post',
            'route' => 'posts.edit',
            'can' => ['update', $post],  // Checks: $user->can('update', $post)
        ],
        
        // Children inherit permissions
        [
            'label' => 'Admin',
            'route' => 'admin.index',
            'can' => 'access-admin',
            'children' => [
                ['label' => 'Users', 'route' => 'admin.users', 'can' => 'manage-users'],
                ['label' => 'Settings', 'route' => 'admin.settings'],
            ],
        ],
    ],
],
```

#### Combining Conditions

Use both `visible` and `can` together:

```php
[
    'label' => 'Billing',
    'route' => 'billing.index',
    'visible' => fn() => config('features.billing_enabled'),
    'can' => 'view-billing',
]
```

#### Conditional Children

Filter child items independently:

```php
[
    'label' => 'Reports',
    'route' => 'reports.index',
    'children' => [
        [
            'label' => 'Sales Report',
            'route' => 'reports.sales',
            'can' => 'view-sales',
        ],
        [
            'label' => 'Financial Report',
            'route' => 'reports.financial',
            'can' => 'view-financials',
        ],
        [
            'label' => 'Admin Report',
            'route' => 'reports.admin',
            'visible' => fn() => auth()->user()?->isAdmin(),
        ],
    ],
]
```

**Note:** If a parent has no visible children, the parent is still shown. If you want to hide the parent when all
children are hidden, add a `visible` check to the parent too.

#### Define Gates in AuthServiceProvider

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('view-users', function ($user) {
        return $user->hasPermission('view-users');
    });
    
    Gate::define('access-admin', function ($user) {
        return $user->isAdmin();
    });
}
```

#### Benefits

✅ **Security** - Items requiring permissions won't appear in navigation  
✅ **Clean UI** - Users only see what they can access  
✅ **DRY** - Reuse existing gates and policies  
✅ **Flexible** - Mix static config with dynamic logic  
✅ **Type Safe** - All authorization goes through Laravel's auth system

### Active State Detection

The package intelligently detects active states:

- **Exact match**: If the current route is `users.index`, that item is active
- **Parent match**: If current route is `users.show`, the parent `users.index` is also marked active
- **Child match**: If current route is `users.roles.index`, both `Users` parent and `Roles` child are active

### External URLs

Mix internal routes with external links:

```php
[
    'label' => 'API Docs',
    'url' => 'https://api.example.com/docs',
    'icon' => 'book-open',
],
```

### Action Items

Define items that trigger POST/DELETE requests:

```php
[
    'label' => 'Logout',
    'route' => 'logout',
    'method' => 'post',
    'icon' => 'log-out',
],
```

### Sections and Separators

Organize your navigation with visual sections and dividers:

```php
'navigations' => [
    'main' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
        
        // Section header
        ['type' => 'section', 'label' => 'Management'],
        
        ['label' => 'Users', 'route' => 'users.index', 'icon' => 'users'],
        ['label' => 'Teams', 'route' => 'teams.index', 'icon' => 'users-2'],
        
        // Visual separator
        ['type' => 'separator'],
        
        ['label' => 'Settings', 'route' => 'settings.index', 'icon' => 'settings'],
        ['label' => 'Logout', 'route' => 'logout', 'method' => 'post', 'icon' => 'log-out'],
    ],
],
```

**Types:**

- `'link'` - Regular navigation item (default)
- `'section'` - Section header for grouping items
- `'separator'` - Visual divider between items

**React Example:**

```tsx
{
    navigation.map((item) => {
        if (item.type === 'section') {
            return (

                {item.label}

            );
        }

        if (item.type === 'separator') {
            return;
        }

        // Regular link rendering
        return {item.label};
    })
}
```

Sections and separators are automatically excluded from breadcrumbs and output with appropriate structure:

```php
[
    ['id' => 'nav-main-0', 'type' => 'link', 'label' => 'Dashboard', ...],
    ['id' => 'nav-main-1', 'type' => 'section', 'label' => 'Management'],
    ['id' => 'nav-main-2', 'type' => 'link', 'label' => 'Users', ...],
    ['id' => 'nav-main-3', 'type' => 'separator'],
    // ...
]
```

### Navigation and Breadcrumb Visibility Control

Control where navigation items appear with `navOnly` and `breadcrumbOnly`:

```php
'navigations' => [
    'main' => [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'children' => [
                ['label' => 'Overview', 'route' => 'dashboard.overview'],
                
                // Hidden from main nav, only appears in breadcrumbs
                [
                    'label' => 'Edit Dashboard',
                    'route' => 'dashboard.edit',
                    'breadcrumbOnly' => true,
                ],
            ],
        ],
        
        // Shown in nav but excluded from breadcrumb trail
        [
            'label' => 'Admin Section',
            'route' => 'admin.index',
            'navOnly' => true,
            'children' => [
                ['label' => 'Users', 'route' => 'admin.users'],
                ['label' => 'Settings', 'route' => 'admin.settings'],
            ],
        ],
    ],
],
```

**Use Cases:**

- `breadcrumbOnly` - Perfect for edit/create pages that shouldn't clutter your main navigation
- `navOnly` - Ideal for grouping/section items that provide structure in navigation but would be redundant in
  breadcrumbs

**Example: Edit Pages**

```php
[
    'label' => 'Users',
    'route' => 'users.index',
    'children' => [
        ['label' => 'All Users', 'route' => 'users.index'],
        ['label' => 'Edit User', 'route' => 'users.edit', 'breadcrumbOnly' => true],
        ['label' => 'Create User', 'route' => 'users.create', 'breadcrumbOnly' => true],
    ],
]
```

The navigation will show only "All Users", but breadcrumbs will display:

```
Users > Edit User
```

**Example: Navigation Sections**

```php
[
    'label' => 'Administration',
    'route' => 'admin.index',
    'navOnly' => true,  // Don't include in breadcrumbs
    'children' => [
        ['label' => 'Manage Users', 'route' => 'admin.users'],
    ],
]
```

Breadcrumbs will show only "Manage Users" without the redundant "Administration" parent.

## Middleware Integration

Share navigation with all Inertia requests:

```php
// app/Http/Middleware/HandleInertiaRequests.php

use SysMatter\Navigation\Facades\Navigation;

public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'navigation' => [
            'main' => Navigation::get('main')->toTree(),
            'user' => Navigation::get('user_menu')->toTree(),
            'footer' => Navigation::get('footer')->toTree(),
        ],
        'breadcrumbs' => Navigation::breadcrumbs('main'),
    ]);
}
```

## Testing

Run the test suite:

```bash
./vendor/bin/pest
```

With coverage:

```bash
./vendor/bin/pest --coverage
```

## Configuration Reference

### Navigation Item Options

| Option           | Type           | Description                                              |
|------------------|----------------|----------------------------------------------------------|
| `type`           | string         | Item type: `'link'`, `'section'`, or `'separator'`       |
| `label`          | string         | Display text for the item                                |
| `route`          | string         | Laravel route name (e.g., `users.index`)                 |
| `url`            | string         | External URL (alternative to `route`)                    |
| `method`         | string         | HTTP method for actions (`post`, `delete`, etc.)         |
| `icon`           | string         | Lucide icon name (e.g., `home`, `users`)                 |
| `children`       | array          | Nested navigation items                                  |
| `visible`        | bool\|callable | Controls visibility (static or dynamic)                  |
| `can`            | string\|array  | Gate/policy check (`'ability'` or `['ability', $model]`) |
| `breadcrumbOnly` | bool           | Only show in breadcrumbs, hide from navigation           |
| `navOnly`        | bool           | Only show in navigation, hide from breadcrumbs           |
| *custom*         | mixed          | Any custom attributes you want to include                |

### Config Options

| Option                | Default                        | Description                          |
|-----------------------|--------------------------------|--------------------------------------|
| `navigations`         | `[]`                           | Array of named navigation structures |
| `icons.compiled_path` | `storage/navigation/icons.php` | Where to save compiled icons         |

## Requirements

- PHP 8.2+, 8.3+, 8.4+
- Laravel 11.0+ or 12.0+

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

Please review [our security policy](SECURITY.md) for reporting vulnerabilities.

## Credits

- [Shavonn Brown](https://github.com/sysmatter)

## License

MIT License. See [LICENSE](LICENSE.md) file for details.
