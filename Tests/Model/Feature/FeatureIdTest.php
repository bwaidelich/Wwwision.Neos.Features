<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\Feature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Neos\Features\Model\Feature\FeatureId;

#[CoversClass(FeatureId::class)]
final class FeatureIdTest extends TestCase
{
    public function test_fromString_exposes_the_given_value(): void
    {
        self::assertSame('some-feature', FeatureId::fromString('some-feature')->value);
    }

    public function test_it_is_castable_to_string(): void
    {
        self::assertSame('some-feature', (string) FeatureId::fromString('some-feature'));
    }

    public function test_equals_is_true_for_the_same_value(): void
    {
        self::assertTrue(FeatureId::fromString('a')->equals(FeatureId::fromString('a')));
    }

    public function test_equals_is_false_for_a_different_value(): void
    {
        self::assertFalse(FeatureId::fromString('a')->equals(FeatureId::fromString('b')));
    }
}
