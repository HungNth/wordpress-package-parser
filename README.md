# WordPress Package Parser

[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-777bb4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Composer](https://img.shields.io/badge/Composer-ready-885630?style=flat-square&logo=composer&logoColor=white)](https://getcomposer.org/)
[![Tests](https://img.shields.io/badge/tests-PHPUnit-6e9f18?style=flat-square)](https://phpunit.de/)

A small framework-agnostic PHP package for reading metadata from WordPress plugin, WordPress theme, and generic ZIP archives.

It extracts useful package data from plugin headers, theme `style.css` headers, and WordPress.org-style `readme.txt` files, then returns a normalized metadata array you can use in Laravel, plain PHP, package upload flows, or custom update systems.

## Features

- Parse WordPress plugin ZIP archives.
- Parse WordPress theme ZIP archives.
- Parse WordPress.org-style `readme.txt` metadata and sections.
- Convert readme sections to HTML with Parsedown.
- Detect generic ZIP archives and generate a stable slug from the uploaded filename.
- Use PHP `ZipArchive` when available, with a bundled PclZip fallback.
- Work without Laravel service providers, facades, or framework bootstrapping.

## Requirements

- PHP 7.4 or newer
- Composer

The package can use the PHP `zip` extension when available. If `ZipArchive` is not loaded, it falls back to the bundled PclZip implementation.

## Installation

```bash
composer require hungnth/wordpress-package-parser
```

## Quick Start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use WpPackageParser\PackageMetadata;

$package = PackageMetadata::fromArchive('/absolute/path/to/my-plugin.zip', 'my-plugin.zip');
$metadata = $package->getMetadata();
```

If you only need the metadata array, use `parse()`:

```php
<?php

use WpPackageParser\PackageMetadata;

$metadata = PackageMetadata::parse('/absolute/path/to/my-plugin.zip', 'my-plugin.zip');
```

## Laravel Usage

No Laravel-specific integration is required. Use it like any other Composer package:

```php
<?php

use WpPackageParser\PackageMetadata;

$package = PackageMetadata::fromArchive(
    $this->uploadedFilePath($file),
    $storedFileName
);

$metadata = $package->getMetadata();
```

Or return the array directly:

```php
$metadata = PackageMetadata::parse(
    $this->uploadedFilePath($file),
    $storedFileName
);
```

## API

### `PackageMetadata::fromArchive()`

```php
PackageMetadata::fromArchive(string $filepath, ?string $originalFilename = null): PackageMetadata
```

Parses a ZIP archive and returns a parsed package metadata object.

- `$filepath` is the readable path to the ZIP archive.
- `$originalFilename` is optional and is used when generating the slug for generic ZIP archives.
- Throws `WpPackageParser\InvalidPackageException` when the file cannot be parsed as a ZIP package.

### `PackageMetadata::parse()`

```php
PackageMetadata::parse(string $filepath, ?string $originalFilename = null): array
```

Convenience method that parses the archive and returns the metadata array directly.

### `$package->getMetadata()`

```php
$package->getMetadata(): array
```

Returns the parsed metadata array.

## Returned Metadata

Plugin archives can return keys such as:

```php
[
    'name' => 'My Plugin',
    'version' => '1.2.3',
    'homepage' => 'https://example.com/my-plugin',
    'author' => 'Jane Developer',
    'author_homepage' => 'https://example.com',
    'requires_php' => '7.4',
    'depends' => ['dependency-one', 'dependency-two'],
    'provides' => ['virtual-one'],
    'requires' => '6.0',
    'tested' => '6.5',
    'sections' => [
        'description' => '<p>Full description.</p>',
    ],
    'upgrade_notice' => 'Upgrade details.',
    'slug' => 'my-plugin',
    'type' => 'plugin',
    'last_updated' => '2026-06-18 10:30:00',
]
```

Theme archives can return similar fields, plus `details_url`. When a theme does not define `Details URI`, `details_url` defaults to `Theme URI`.

Generic ZIP archives return package-level metadata:

```php
[
    'slug' => 'custom-slug-generated-from-filename',
    'type' => 'generic',
    'last_updated' => '2026-06-18 10:30:00',
]
```

> [!NOTE]
> Metadata keys depend on which headers and readme fields exist in the archive. Missing headers are not included in the returned array.

## Supported Archive Layout

The parser scans files at the archive root and one directory level below it, which matches common WordPress package layouts:

```text
my-plugin.zip
└── my-plugin/
    ├── my-plugin.php
    └── readme.txt
```

```text
my-theme.zip
└── my-theme/
    └── style.css
```

For plugins, the main PHP file must include a valid `Plugin Name` header. For themes, `style.css` must include a valid `Theme Name` header.

## Error Handling

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

`InvalidPackageException` is thrown when the archive path is missing, unreadable, or not a valid ZIP package.

## Credits

This package builds on ideas and code patterns from:

- [YahnisElsts/wp-update-server](https://github.com/YahnisElsts/wp-update-server) for WordPress package parsing and update-server metadata concepts.
- [erusev/parsedown](https://github.com/erusev/parsedown) for Markdown-to-HTML conversion of readme sections.

## Development

Install dependencies:

```bash
composer update
```

Run the test suite:

```bash
composer test
```

Run syntax checks:

```bash
php -l src/PackageMetadata.php
php -l src/PackageParser.php
```

> [!IMPORTANT]
> The test suite creates ZIP fixtures with `ZipArchive`, so local development tests require the PHP `zip` extension even though runtime parsing has a PclZip fallback.
