<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonOptions;

use Wwwision\Types\Attributes\StringBased;

use Wwwision\Types\Schema\StringTypeFormat;

use function Wwwision\Types\instantiate;

#[StringBased(format: StringTypeFormat::uri)]
final readonly class Url
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        return instantiate(self::class, $value);
    }
}
