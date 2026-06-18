# WordPress Package Parser

A small PHP library for reading metadata from WordPress plugin, theme, and generic ZIP packages.

## Requirements

- PHP 7.4 or newer
- Composer

The package can use PHP's `ZipArchive` extension when available. It also includes a PclZip fallback for environments without `ZipArchive`.

## Installation

```bash
composer require hungnth/wordpress-package-parser
```

## Plain PHP Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use WpPackageParser\PackageMetadata;

$package = PackageMetadata::fromArchive('/absolute/path/to/plugin.zip', 'plugin.zip');
$metadata = $package->getMetadata();
```

Use `parse()` when you only need the metadata array:

```php
$metadata = PackageMetadata::parse('/absolute/path/to/plugin.zip', 'plugin.zip');
```

## Laravel Usage

No service provider or Laravel facade is required. Use the package like any other Composer library:

```php
<?php

use WpPackageParser\PackageMetadata;

$package = PackageMetadata::fromArchive(
    $this->uploadedFilePath($file),
    $storedFileName
);

$metadata = $package->getMetadata();
```

## Returned Metadata

Plugin packages can return keys such as:

```php
[
    'name' => 'My Plugin',
    'version' => '1.2.3',
    'homepage' => 'https://example.com/my-plugin',
    'author' => 'Jane Developer',
    'author_homepage' => 'https://example.com',
    'requires_php' => '7.4',
    'requires' => '6.0',
    'tested' => '6.5',
    'sections' => [
        'description' => '<p>Full description.</p>',
    ],
    'upgrade_notice' => 'Upgrade details.',
    'last_updated' => '2026-06-18 10:30:00',
    'slug' => 'my-plugin',
]
```

Theme packages can also return `details_url`. When the theme does not define `Details URI`, `details_url` defaults to `Theme URI`.

Generic ZIP files return a slug based on the original filename:

```php
[
    'slug' => 'custom-upload',
]
```

## Error Handling

Invalid or unreadable package paths throw `WpPackageParser\InvalidPackageException`:

```php
<?php

use WpPackageParser\InvalidPackageException;
use WpPackageParser\PackageMetadata;

try {
    $metadata = PackageMetadata::parse('/absolute/path/to/package.zip', 'package.zip');
} catch (InvalidPackageException $exception) {
    error_log($exception->getMessage());
}
```

## Development

Install dev dependencies and run tests:

```bash
composer update
composer test
```
