<?php

declare(strict_types=1);

namespace WpPackageParser\Tests;

use PHPUnit\Framework\TestCase;
use WpPackageParser\PackageMetadata;
use ZipArchive;

final class PackageMetadataTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $tempFile) {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }

        $this->tempFiles = [];
    }

    public function testFromArchiveParsesPluginPackageMetadata(): void
    {
        $packagePath = $this->createZip('my-plugin.zip', [
            'my-plugin/my-plugin.php' => <<<'PHP'
<?php
/**
 * Plugin Name: My Plugin
 * Plugin URI: https://example.com/my-plugin
 * Version: 1.2.3
 * Author: Jane Developer
 * Author URI: https://example.com
 * Requires PHP: 7.4
 * Depends: dependency-one, dependency-two
 * Provides: virtual-one
 */
PHP,
            'my-plugin/readme.txt' => <<<'README'
=== My Plugin ===
Contributors: jane
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.2.3

Short description.

== Description ==
Full description.

== Upgrade Notice ==
= 1.2.3 =
Upgrade details.
README,
        ]);

        $package = PackageMetadata::fromArchive($packagePath, 'my-plugin.zip');
        $metadata = $package->getMetadata();

        self::assertSame('My Plugin', $metadata['name']);
        self::assertSame('1.2.3', $metadata['version']);
        self::assertSame('https://example.com/my-plugin', $metadata['homepage']);
        self::assertSame('Jane Developer', $metadata['author']);
        self::assertSame('https://example.com', $metadata['author_homepage']);
        self::assertSame('7.4', $metadata['requires_php']);
        self::assertSame(['dependency-one', 'dependency-two'], $metadata['depends']);
        self::assertSame(['virtual-one'], $metadata['provides']);
        self::assertSame('6.0', $metadata['requires']);
        self::assertSame('6.5', $metadata['tested']);
        self::assertSame('my-plugin', $metadata['slug']);
        self::assertSame('<p>Full description.</p>', $metadata['sections']['description']);
        self::assertSame('Upgrade details.', $metadata['upgrade_notice']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $metadata['last_updated']);
    }

    /**
     * @param array<string, string> $files
     */
    private function createZip(string $filename, array $files): string
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('The ext-zip extension is required to create ZIP test fixtures.');
        }

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wpp-' . bin2hex(random_bytes(8)) . '-' . $filename;
        $zip = new ZipArchive();
        $opened = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        self::assertTrue($opened === true, 'Unable to create ZIP fixture at ' . $path);

        foreach ($files as $entryName => $contents) {
            self::assertTrue($zip->addFromString($entryName, $contents), 'Unable to add ZIP entry ' . $entryName);
        }

        self::assertTrue($zip->close(), 'Unable to close ZIP fixture at ' . $path);

        $this->tempFiles[] = $path;

        return $path;
    }
}
