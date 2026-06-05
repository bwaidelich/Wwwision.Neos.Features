<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonOptions;

use DateTimeInterface;
use Wwwision\Types\Attributes\StringBased;

use Wwwision\Types\Schema\StringTypeFormat;

use function Wwwision\Types\instantiate;

#[StringBased(format: StringTypeFormat::date)]
final readonly class Date
{
    private const string FORMAT = 'Y-m-d';

    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        return instantiate(self::class, $value);
    }

    public static function fromPhpDateTime(DateTimeInterface $dateTime): self
    {
        return instantiate(self::class, $dateTime->format(self::FORMAT));
    }
}
