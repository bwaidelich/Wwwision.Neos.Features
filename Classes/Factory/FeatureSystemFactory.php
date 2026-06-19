<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Factory;

use Wwwision\Neos\Features\Adapter\FeatureProviderFromSettings;
use Wwwision\Neos\Features\Adapter\ForStoringFeatureStatesViaYaml;
use Wwwision\Neos\Features\FeatureSystem;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;

final class FeatureSystemFactory
{
    public function __construct(
        private readonly FeatureProviderFromSettings $featureProvider,
        private readonly ForStoringFeatureStatesViaYaml $forStoringFeatureStates,
        private readonly FeatureContext $featureContext,
    ) {}

    public function create(): FeatureSystem
    {
        return new FeatureSystem($this->featureProvider, $this->forStoringFeatureStates, $this->featureContext);
    }

}
