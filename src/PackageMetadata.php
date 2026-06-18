<?php

namespace WpPackageParser;

final class PackageMetadata
{
    private const HEADER_MAP = array(
        'Name' => 'name',
        'Version' => 'version',
        'PluginURI' => 'homepage',
        'ThemeURI' => 'homepage',
        'Author' => 'author',
        'AuthorURI' => 'author_homepage',
        'RequiresPHP' => 'requires_php',
        'DetailsURI' => 'details_url',
        'Depends' => 'depends',
        'Provides' => 'provides',
    );

    private const README_MAP = array(
        'requires',
        'tested',
        'requires_php',
    );

    private array $packageInfo = [];

    private string $filepath;

    private array $metadata = [];

    private string $originalFilename;

    private function __construct(string $filepath, string $originalFilename)
    {
        $this->filepath = $filepath;
        $this->originalFilename = $originalFilename;
    }

    public static function fromArchive(string $filepath, ?string $originalFilename = null): self
    {
        $package = new self($filepath, $originalFilename ?: basename($filepath));
        $package->extractMetadata();

        return $package;
    }

    public static function parse(string $filepath, ?string $originalFilename = null): array
    {
        return self::fromArchive($filepath, $originalFilename)->getMetadata();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @throws InvalidPackageException if the input file cannot be parsed as a ZIP package.
     */
    private function extractMetadata(): void
    {
        $packageInfo = PackageParser::parsePackage($this->filepath, true);

        if (! \is_array($packageInfo) || empty($packageInfo)) {
            throw new InvalidPackageException(\sprintf('The specified file %s could not be parsed as a ZIP package.', $this->filepath));
        }

        $this->packageInfo = $packageInfo;
        $this->metadata = [];

        $this->setMetadata();
    }

    private function setMetadata(): void
    {
        if (isset($this->packageInfo['type']) && $this->packageInfo['type'] !== 'generic') {
            $this->setInfoFromHeader();
            $this->setInfoFromReadme();
        }

        $this->setSlug();
        $this->setPackageType();
        $this->setLastUpdateDate();
    }

    private function setPackageType(): void
    {
        $this->metadata['type'] = $this->packageInfo['type'];
    }

    private function setInfoFromHeader(): void
    {
        if (isset($this->packageInfo['header']) && \is_array($this->packageInfo['header']) && ! empty($this->packageInfo['header'])) {
            $this->setMappedFields($this->packageInfo['header'], self::HEADER_MAP);
            $this->setThemeDetailsUrl();
        }
    }

    private function setInfoFromReadme(): void
    {
        if (isset($this->packageInfo['readme']) && \is_array($this->packageInfo['readme']) && ! empty($this->packageInfo['readme'])) {
            $readmeMap = array_combine(array_values(self::README_MAP), self::README_MAP);

            if ($readmeMap !== false) {
                $this->setMappedFields($this->packageInfo['readme'], $readmeMap);
            }

            $this->setReadmeSections();
            $this->setReadmeUpgradeNotice();
        }
    }

    private function setMappedFields(array $input, array $map): void
    {
        foreach ($map as $fieldKey => $metaKey) {
            if (! empty($input[$fieldKey])) {
                $this->metadata[$metaKey] = $input[$fieldKey];
            }
        }
    }

    private function setThemeDetailsUrl(): void
    {
        if ($this->packageInfo['type'] === 'theme' && ! isset($this->metadata['details_url']) && isset($this->metadata['homepage'])) {
            $this->metadata['details_url'] = $this->metadata['homepage'];
        }
    }

    private function setReadmeSections(): void
    {
        if (isset($this->packageInfo['readme']['sections']) && \is_array($this->packageInfo['readme']['sections']) && $this->packageInfo['readme']['sections'] !== array()) {
            foreach ($this->packageInfo['readme']['sections'] as $sectionName => $sectionContent) {
                $sectionName = str_replace(' ', '_', strtolower((string) $sectionName));
                $this->metadata['sections'][$sectionName] = $sectionContent;
            }
        }
    }

    private function setReadmeUpgradeNotice(): void
    {
        if (isset($this->metadata['sections']['upgrade_notice'], $this->metadata['version'])) {
            $regex = '@<h4>\s*' . preg_quote((string) $this->metadata['version']) . '\s*</h4>[^<>]*?<p>(.+?)</p>@i';

            if (preg_match($regex, (string) $this->metadata['sections']['upgrade_notice'], $matches)) {
                $this->metadata['upgrade_notice'] = trim(strip_tags($matches[1]));
            }
        }
    }

    private function setLastUpdateDate(): void
    {
        if (! isset($this->metadata['last_updated'])) {
            $this->metadata['last_updated'] = date('Y-m-d H:i:s', filemtime($this->filepath));
        }
    }

    private function setSlug(): void
    {
        if ($this->packageInfo['type'] === 'generic') {
            $this->metadata['slug'] = pathinfo($this->originalFilename, PATHINFO_FILENAME);

            return;
        }

        $mainFile = $this->packageInfo['type'] === 'plugin'
            ? $this->packageInfo['pluginFile']
            : $this->packageInfo['stylesheet'];

        $this->metadata['slug'] = basename(dirname((string) $mainFile));
    }
}
