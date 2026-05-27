<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Factory;

use Wwwision\Neos\Features\Adapter\ForProvidingFeatureDefinitionsFromSettings;
use Wwwision\Neos\Features\Adapter\ForStoringFeatureStatesViaYaml;
use Wwwision\Neos\Features\FeatureSystem;

final readonly class FeatureSystemFactory
{
    public function __construct(
        private ForProvidingFeatureDefinitionsFromSettings $forProvidingFeatureDefinitions,
        private ForStoringFeatureStatesViaYaml $forStoringFeatureStates,
    ) {}

    public function create(): FeatureSystem
    {
        return new FeatureSystem($this->forProvidingFeatureDefinitions, $this->forStoringFeatureStates);
    }

}
