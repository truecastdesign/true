# SEO Class Documentation

## Overview
The **SEO** class provides tools to generate structured data using JSON+LD and create breadcrumb navigation schema. It supports various schema types, including recipes, articles, organizations, and more. The class aims to improve search engine visibility by integrating schema markup into web pages.

---

## Public Methods

### `jsonLD(string $type, $info)`
Generates a JSON+LD script for the specified schema type.

#### Parameters
- **`$type`** (string): The type of schema to generate. Supported values include:
  - `recipe`
  - `article`
  - `organization`
  - `website`
  - `webpage`
  - `blogpost`
  - `breadcrumbs`
  - `store`
  - `homeandconstructionbusiness`
  - `product`
- **`$info`** (array|object): An associative array or object containing the necessary data for the schema.

#### Returns
- (string): A string containing the generated JSON+LD script, ready to be embedded in an HTML page.

#### Example Usage
```php
use True\SEO;

$seo = new SEO();
$info = [
    'name' => 'Chocolate Cake',
    'description' => 'A delicious chocolate cake recipe',
    'author' => 'John Doe'
];

echo $seo->jsonLD('recipe', $info);
```

#### Output
```html
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "Recipe",
    "name": "Chocolate Cake",
    "description": "A delicious chocolate cake recipe",
    "author": "John Doe"
}
</script>
```

---

### `generateBreadcrumbs($lookupFile = null)`
Generates an array of path and names for the BreadcrumbList schema.

#### Parameters
- **`$lookupFile`** (string|null): The full file path to an INI file containing custom page name lookups. Example:
  ```ini
  / = "Home"
  /contact.html = "Contact Us"
  ```

#### Returns
- (array): An associative array of breadcrumb paths and names.

#### Example Usage
```php
use True\SEO;

$seo = new SEO();
$breadcrumbs = $seo->generateBreadcrumbs('/path/to/lookup.ini');
print_r($breadcrumbs);
```

---

### `schemaGraph(object $info)`
Generates a schema graph using structured data for a webpage.

#### Parameters
- **`$info`** (object): An object containing details for the schema graph, including:
  - `url`
  - `title`
  - `description`
  - `site_logo_url`
  - `site_logo_width`
  - `site_logo_height`
  - `site_logo_caption`
  - `datePublished`
  - `dateModified`
  - `social_media` (array): An array of social media links.
  - `breadcrumbs` (array): An array of breadcrumb items.

#### Returns
- (string): A string containing the generated JSON+LD script for the schema graph.

#### Example Usage
```php
use True\SEO;

$seo = new SEO();
$info = (object) [
    'url' => 'https://example.com',
    'title' => 'Example Website',
    'description' => 'An example website description',
    'site_logo_url' => 'https://example.com/logo.png',
    'site_logo_width' => 300,
    'site_logo_height' => 100,
    'datePublished' => '2023-01-01',
    'dateModified' => '2024-01-01',
    'social_media' => [
        'https://facebook.com/example',
        'https://twitter.com/example'
    ],
    'breadcrumbs' => [
        ["name" => "Home", "url" => "/"],
        ["name" => "About", "url" => "/about/"]
    ]
];

echo $seo->schemaGraph($info);
```

---

## Summary
The **SEO** class offers a straightforward way to generate structured data in JSON+LD format for various schema types. It enhances SEO by providing search engines with detailed information about your website's content, improving visibility and search ranking.

