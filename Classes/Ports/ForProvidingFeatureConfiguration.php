<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Ports;

use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinitions;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroups;

/**
 * Provides the statically configured feature catalog: the {@see FeatureDefinitions} and the {@see FeatureGroups}
 * they may be assigned to.
 */
interface ForProvidingFeatureConfiguration
{
    public function getFeatureDefinitions(): FeatureDefinitions;

    public function getFeatureGroups(): FeatureGroups;
}
