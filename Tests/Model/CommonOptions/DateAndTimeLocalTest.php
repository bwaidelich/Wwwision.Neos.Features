<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\CommonOptions;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Neos\Features\Model\CommonOptions\DateAndTimeLocal;
use Wwwision\Types\Exception\CoerceException;

#[CoversClass(DateAndTimeLocal::class)]
final class DateAndTimeLocalTest extends TestCase
{
    public function test_fromString_accepts_a_valid_value(): void
    {
        self::assertSame('2026-07-03T14:30', DateAndTimeLocal::fromString('2026-07-03T14:30')->value);
    }

    public function test_fromString_rejects_a_value_with_seconds(): void
    {
        $this->expectException(CoerceException::class);
        DateAndTimeLocal::fromString('2026-07-03T14:30:00');
    }

    public function test_fromPhpDateTime_converts_the_given_date_to_UTC(): void
    {
        $dateTime = new DateTimeImmutable('2026-07-03 14:30:00', new DateTimeZone('Europe/Berlin'));

        self::assertSame('2026-07-03T12:30', DateAndTimeLocal::fromPhpDateTime($dateTime)->value);
    }

    public function test_toPhpDateTime_round_trips_the_value(): void
    {
        $dateAndTime = DateAndTimeLocal::fromString('2026-07-03T14:30');

        self::assertSame('2026-07-03T14:30', $dateAndTime->toPhpDateTime()->format('Y-m-d\TH:i'));
    }
}
