<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Ports;

use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\FeatureState\FeatureState;
use Wwwision\Neos\Features\Model\FeatureState\FeatureStates;

interface ForStoringFeatureStates
{
    public function store(FeatureState $featureState): void;

    public function remove(FeatureId $featureId): void;

    public function loadAll(): FeatureStates;
}
