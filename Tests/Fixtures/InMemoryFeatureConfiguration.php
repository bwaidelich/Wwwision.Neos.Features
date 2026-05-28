<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Fixtures;

use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinitions;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroups;
use Wwwision\Neos\Features\Ports\ForProvidingFeatureConfiguration;

/**
 * In-memory implementation of {@see ForProvidingFeatureConfiguration} for use in tests.
 */
final readonly class InMemoryFeatureConfiguration implements ForProvidingFeatureConfiguration
{
    private FeatureGroups $featureGroups;

    public function __construct(
        private FeatureDefinitions $featureDefinitions,
        FeatureGroups|null $featureGroups = null,
    ) {
        $this->featureGroups = $featureGroups ?? FeatureGroups::fromArray([]);
    }

    public function getFeatureDefinitions(): FeatureDefinitions
    {
        return $this->featureDefinitions;
    }

    public function getFeatureGroups(): FeatureGroups
    {
        return $this->featureGroups;
    }
}
