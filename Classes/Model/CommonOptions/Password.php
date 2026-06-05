<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonOptions;

use SensitiveParameter;
use Wwwision\Types\Attributes\StringBased;

use function Wwwision\Types\instantiate;

#[StringBased(extensions: ['x-feature-editor' => 'password'])]
final readonly class Password
{
    private function __construct(
        #[SensitiveParameter]
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        return instantiate(self::class, $value);
    }
}
