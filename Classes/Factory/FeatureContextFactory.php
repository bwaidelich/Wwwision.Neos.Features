<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Factory;

use Wwwision\Neos\Features\Model\CommonFeatures\YamlConfigurationFile;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;

final class FeatureContextFactory
{
    /**
     * @param array<string, mixed> $flowSettings The "Neos.Flow" settings, required by {@see FeatureContext::executeCommand()}
     */
    public function __construct(
        private readonly string $settingsFilePath,
        private readonly string $nodeTypesFilePath,
        private readonly array $flowSettings,
    ) {}

    public function create(): FeatureContext
    {
        return new FeatureContext(
            new YamlConfigurationFile($this->settingsFilePath),
            new YamlConfigurationFile($this->nodeTypesFilePath),
            $this->flowSettings,
        );
    }
}
