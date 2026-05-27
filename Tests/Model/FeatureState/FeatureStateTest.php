<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\FeatureState;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\FeatureState\FeatureState;

#[CoversClass(FeatureState::class)]
final class FeatureStateTest extends TestCase
{
    public function test_it_exposes_the_constructor_values(): void
    {
        $state = new FeatureState(FeatureId::fromString('f'), true, ['k' => 'v']);
        self::assertSame('f', $state->featureId->value);
        self::assertTrue($state->active);
        self::assertSame(['k' => 'v'], $state->options);
    }

    public function test_with_returns_an_unchanged_copy_when_no_arguments_are_given(): void
    {
        $state = new FeatureState(FeatureId::fromString('f'), true, ['k' => 'v']);
        $copy = $state->with();
        self::assertNotSame($state, $copy);
        self::assertEquals($state, $copy);
    }

    public function test_with_overrides_only_the_active_flag(): void
    {
        $state = new FeatureState(FeatureId::fromString('f'), true, ['k' => 'v']);
        $copy = $state->with(active: false);
        self::assertFalse($copy->active);
        self::assertSame(['k' => 'v'], $copy->options);
        self::assertTrue($state->featureId->equals($copy->featureId));
    }

    public function test_with_overrides_only_the_options(): void
    {
        $state = new FeatureState(FeatureId::fromString('f'), true, ['k' => 'v']);
        $copy = $state->with(options: ['other' => 1]);
        self::assertTrue($copy->active);
        self::assertSame(['other' => 1], $copy->options);
    }
}
