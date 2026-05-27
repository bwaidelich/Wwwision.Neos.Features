<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\Feature;

final readonly class FeatureActivateResult
{
    private function __construct(
        public bool $success,
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

}
