<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureGroup;

use Stringable;
use Wwwision\Types\Attributes\StringBased;

use function Wwwision\Types\instantiate;

#[StringBased]
final readonly class FeatureGroupDescription implements Stringable
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        return instantiate(self::class, $value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
