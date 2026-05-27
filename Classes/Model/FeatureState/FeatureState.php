<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureState;

use Wwwision\Neos\Features\Model\Feature\FeatureId;

final readonly class FeatureState
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public FeatureId $featureId,
        public bool $active,
        public array $options,
    ) {}

    /**
     * @param array<string, mixed>|null $options
     */
    public function with(
        bool|null $active = null,
        array|null $options = null,
    ): self {
        return new self(
            $this->featureId,
            $active ?? $this->active,
            $options ?? $this->options,
        );
    }
}
