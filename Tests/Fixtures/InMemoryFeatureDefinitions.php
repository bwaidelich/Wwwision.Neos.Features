<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Fixtures;

use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinitions;
use Wwwision\Neos\Features\Ports\ForProvidingFeatureDefinitions;

/**
 * In-memory implementation of {@see ForProvidingFeatureDefinitions} for use in tests.
 */
final readonly class InMemoryFeatureDefinitions implements ForProvidingFeatureDefinitions
{
    public function __construct(
        private FeatureDefinitions $featureDefinitions,
    ) {}

    public function getFeatureDefinitions(): FeatureDefinitions
    {
        return $this->featureDefinitions;
    }
}
