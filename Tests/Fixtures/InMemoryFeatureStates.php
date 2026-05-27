<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Fixtures;

use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\FeatureState\FeatureState;
use Wwwision\Neos\Features\Model\FeatureState\FeatureStates;
use Wwwision\Neos\Features\Ports\ForStoringFeatureStates;

/**
 * In-memory implementation of {@see ForStoringFeatureStates} for use in tests.
 */
final class InMemoryFeatureStates implements ForStoringFeatureStates
{
    /**
     * @var array<string, FeatureState>
     */
    private array $states = [];

    public function store(FeatureState $featureState): void
    {
        $this->states[$featureState->featureId->value] = $featureState;
    }

    public function remove(FeatureId $featureId): void
    {
        unset($this->states[$featureId->value]);
    }

    public function loadAll(): FeatureStates
    {
        return FeatureStates::fromArray(array_values($this->states));
    }
}
