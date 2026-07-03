<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\FeatureState;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\FeatureState\FeatureState;
use Wwwision\Neos\Features\Model\FeatureState\FeatureStates;

#[CoversClass(FeatureStates::class)]
#[UsesClass(FeatureId::class)]
#[UsesClass(FeatureState::class)]
final class FeatureStatesTest extends TestCase
{
    public function test_get_returns_the_state_matching_the_given_id(): void
    {
        $stateA = new FeatureState(FeatureId::fromString('a'), true, []);
        $stateB = new FeatureState(FeatureId::fromString('b'), false, []);
        $states = FeatureStates::fromArray([$stateA, $stateB]);

        self::assertSame($stateB, $states->get(FeatureId::fromString('b')));
    }

    public function test_get_returns_null_when_no_state_matches(): void
    {
        $states = FeatureStates::fromArray([new FeatureState(FeatureId::fromString('a'), true, [])]);

        self::assertNull($states->get(FeatureId::fromString('missing')));
    }

    public function test_it_is_iterable_over_the_given_states(): void
    {
        $stateA = new FeatureState(FeatureId::fromString('a'), true, []);
        $stateB = new FeatureState(FeatureId::fromString('b'), false, []);
        $states = FeatureStates::fromArray([$stateA, $stateB]);

        self::assertSame([$stateA, $stateB], iterator_to_array($states));
    }

    public function test_an_empty_collection_yields_no_items(): void
    {
        self::assertSame([], iterator_to_array(FeatureStates::fromArray([])));
    }
}
