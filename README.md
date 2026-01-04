# Selectbox Tree

Simple and elegant tree builder that transforms hierarchical data structures into flat selectbox-friendly arrays with visual indentation.

![Sample selectbox view](doc/selectbox-design.png)

## :sparkles: Key Features

- Transforms parent-child tree structures into flat arrays suitable for HTML `<select>` elements
- Automatic visual indentation using pipe characters (`|`) and non-breaking spaces to represent hierarchy depth
- Built-in SQL query builder helper for fetching hierarchical data from databases
- Configurable maximum depth to prevent infinite recursion
- Custom name formatting through the `NameFormatter` interface
- Optional integration with `Baraja\Localization\Translation` for multilingual support
- Supports both raw array input and typed `SelectboxItem` objects
- Zero external dependencies (PHP 8.0+ only)

## :building_construction: Architecture

The library consists of three main components:

```
┌─────────────────────────────────────────────────────────────┐
│                      SelectboxTree                          │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  process(array $data): array                        │   │
│  │  - Accepts SelectboxItem[] or raw arrays            │   │
│  │  - Builds hierarchical structure                    │   │
│  │  - Returns flat array with visual indentation       │   │
│  └─────────────────────────────────────────────────────┘   │
│                           │                                 │
│                           ▼                                 │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  serializeCategoriesToSelectbox()                   │   │
│  │  - Recursive tree traversal                         │   │
│  │  - Tracks used IDs to prevent duplicates            │   │
│  │  - Respects maxDepth limit                          │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
           │                               │
           ▼                               ▼
┌─────────────────────┐       ┌─────────────────────────┐
│   SelectboxItem     │       │    NameFormatter        │
│  ┌───────────────┐  │       │      (interface)        │
│  │ id            │  │       │  ┌───────────────────┐  │
│  │ name          │  │       │  │ format(string):   │  │
│  │ parentId      │  │       │  │   string          │  │
│  └───────────────┘  │       │  └───────────────────┘  │
└─────────────────────┘       └─────────────────────────┘
```

### :jigsaw: Components Overview

| Component | Type | Description |
|-----------|------|-------------|
| `SelectboxTree` | Class | Main processor that converts tree data to selectbox array |
| `SelectboxItem` | Class | Immutable value object representing a single tree node |
| `NameFormatter` | Interface | Contract for custom name transformation logic |

## :gear: How It Works

### Tree Transformation Process

The library takes hierarchical data where each item has an `id`, `name`, and optional `parent_id`, then:

1. **Normalizes input** - Accepts either `SelectboxItem` objects or raw associative arrays
2. **Applies name formatting** - If a `NameFormatter` is set, transforms each item's name
3. **Builds tree recursively** - Starting from root items (where `parent_id` is `null`), traverses children depth-first
4. **Generates visual indentation** - Prepends pipe characters and non-breaking spaces based on depth level
5. **Returns flat array** - Keys are item IDs, values are formatted names with indentation

### Visual Indentation Format

Each level of depth adds the pattern `|   ` (pipe followed by three non-breaking spaces):

```
Root Item
|   First Level Child
|   |   Second Level Child
|   |   |   Third Level Child
```

### Input Data Format

The `process()` method accepts an array of items in two formats:

**Array format:**
```php
[
    ['id' => 1, 'name' => 'Electronics', 'parent_id' => null],
    ['id' => 2, 'name' => 'Phones', 'parent_id' => 1],
    ['id' => 3, 'name' => 'iPhone', 'parent_id' => 2],
]
```

**SelectboxItem format:**
```php
[
    new SelectboxItem(1, 'Electronics', null),
    new SelectboxItem(2, 'Phones', 1),
    new SelectboxItem(3, 'iPhone', 2),
]
```

### Output Format

The `process()` method returns an associative array where:
- **Keys** are the original item IDs (`int|string`)
- **Values** are the formatted names with visual indentation (`string`)

```php
[
    1 => 'Electronics',
    2 => '|   Phones',
    3 => '|   |   iPhone',
]
```

## :package: Installation

It's best to use [Composer](https://getcomposer.org) for installation, and you can also find the package on
[Packagist](https://packagist.org/packages/baraja-core/selectbox-tree) and
[GitHub](https://github.com/baraja-core/selectbox-tree).

To install, simply use the command:

```shell
$ composer require baraja-core/selectbox-tree
```

You can use the package manually by creating an instance of the internal classes, or register a DIC extension to link the services directly to the Nette Framework.

### Requirements

- PHP 8.0 or higher

## :rocket: Basic Usage

### Simple Example

```php
use Baraja\SelectboxTree\SelectboxTree;

$tree = new SelectboxTree();

$data = [
    ['id' => 1, 'name' => 'Main category', 'parent_id' => null],
    ['id' => 2, 'name' => 'Phone', 'parent_id' => 1],
    ['id' => 3, 'name' => 'iPhone', 'parent_id' => 2],
    ['id' => 4, 'name' => 'Computer', 'parent_id' => 1],
    ['id' => 5, 'name' => 'Mac', 'parent_id' => 4],
    ['id' => 6, 'name' => 'MacBook', 'parent_id' => 5],
    ['id' => 7, 'name' => 'iMac', 'parent_id' => 5],
    ['id' => 8, 'name' => 'Windows', 'parent_id' => 4],
];

$selectboxOptions = $tree->process($data);

// Result:
// [
//     1 => 'Main category',
//     2 => '|   Phone',
//     3 => '|   |   iPhone',
//     4 => '|   Computer',
//     5 => '|   |   Mac',
//     6 => '|   |   |   MacBook',
//     7 => '|   |   |   iMac',
//     8 => '|   |   Windows',
// ]
```

### Using with HTML Select Element

```php
use Baraja\SelectboxTree\SelectboxTree;

$tree = new SelectboxTree();
$options = $tree->process($categories);

echo '<select name="category">';
foreach ($options as $id => $name) {
    echo sprintf('<option value="%s">%s</option>', $id, htmlspecialchars($name));
}
echo '</select>';
```

### Using SelectboxItem Objects

```php
use Baraja\SelectboxTree\SelectboxTree;
use Baraja\SelectboxTree\SelectboxItem;

$tree = new SelectboxTree();

$items = [
    new SelectboxItem(1, 'Electronics', null),
    new SelectboxItem(2, 'Phones', 1),
    new SelectboxItem(3, 'Computers', 1),
];

$selectboxOptions = $tree->process($items);
```

## :wrench: Advanced Configuration

### Setting Maximum Depth

To prevent infinite recursion or limit the tree depth, use `setMaxDepth()`:

```php
$tree = new SelectboxTree();
$tree->setMaxDepth(5); // Limit to 5 levels deep

$options = $tree->process($data);
```

**Constraints:**
- Minimum value: `1`
- Maximum value: `1000`
- Default value: `32`

### Custom Name Formatting

Implement the `NameFormatter` interface to transform item names:

```php
use Baraja\SelectboxTree\SelectboxTree;
use Baraja\SelectboxTree\NameFormatter;

class UppercaseFormatter implements NameFormatter
{
    public function format(string $name): string
    {
        return strtoupper($name);
    }
}

$tree = new SelectboxTree();
$tree->setNameFormatter(new UppercaseFormatter());

$options = $tree->process($data);
// All names will be uppercase
```

### SQL Query Builder

The library includes a helper method to generate SQL queries for fetching hierarchical data:

```php
$tree = new SelectboxTree();

// Basic usage
$sql = $tree->sqlBuilder('categories');
// SELECT `id`, `name`, `parent_id` FROM `categories` ORDER BY `name` ASC

// Custom columns
$sql = $tree->sqlBuilder(
    table: 'products',
    primaryCol: 'title',
    parentCol: 'category_id',
);
// SELECT `id`, `title`, `category_id` FROM `products` ORDER BY `title` ASC

// With WHERE conditions
$sql = $tree->sqlBuilder(
    table: 'categories',
    wheres: ['active = 1', 'deleted_at IS NULL'],
);
// SELECT `id`, `name`, `parent_id` FROM `categories`
// WHERE (active = 1) AND (deleted_at IS NULL) ORDER BY `name` ASC

// Custom ORDER BY
$sql = $tree->sqlBuilder(
    table: 'categories',
    orderCol: 'position',
);
// SELECT `id`, `name`, `parent_id` FROM `categories` ORDER BY `position` ASC
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$table` | `string` | required | Database table name |
| `$primaryCol` | `string` | `'name'` | Column containing the display name |
| `$parentCol` | `string` | `'parent_id'` | Column containing the parent reference |
| `$wheres` | `array` | `[]` | Array of WHERE conditions |
| `$orderCol` | `?string` | `null` | Column for ORDER BY (defaults to `$primaryCol`) |

### Integration with Database

```php
use Baraja\SelectboxTree\SelectboxTree;

$tree = new SelectboxTree();

// Generate SQL query
$sql = $tree->sqlBuilder('categories', 'name', 'parent_id', ['active = 1']);

// Fetch data from database (using your preferred method)
$pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'pass');
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process into selectbox format
$options = $tree->process($data);
```

## :globe_with_meridians: Localization Support

The library automatically integrates with `Baraja\Localization\Translation` if available. Names starting with `T:{` pattern are automatically translated:

```php
$data = [
    ['id' => 1, 'name' => 'T:{"cs":"Kategorie","en":"Category"}', 'parent_id' => null],
];

$tree = new SelectboxTree();
$options = $tree->process($data);
// The name will be automatically translated based on current locale
```

This feature is optional and only activates when the `baraja-core/localization` package is installed.

## :shield: Safety Features

### Duplicate Prevention

The library tracks processed IDs to prevent duplicate entries in the output, even if the same item appears multiple times in the input data.

### Infinite Recursion Protection

Two mechanisms prevent infinite loops:

1. **Max depth limit** - Configurable via `setMaxDepth()`, defaults to 32 levels
2. **Used ID tracking** - Items are only processed once, preventing circular references

### Input Validation

- Max depth must be between 1 and 1000
- Empty or null input arrays are handled gracefully (return empty array)

## :busts_in_silhouette: Author

**Jan Barasek**
[https://baraja.cz](https://baraja.cz)

## :page_facing_up: License

`baraja-core/selectbox-tree` is licensed under the MIT license. See the [LICENSE](https://github.com/baraja-core/selectbox-tree/blob/master/LICENSE) file for more details.
