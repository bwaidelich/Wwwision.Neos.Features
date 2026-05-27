<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Ports;

use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinitions;

interface ForProvidingFeatureDefinitions
{
    public function getFeatureDefinitions(): FeatureDefinitions;

}
