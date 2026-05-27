<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureState;

use IteratorAggregate;
use Traversable;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Types\Attributes\ListBased;

use function Wwwision\Types\instantiate;

/**
 * @implements IteratorAggregate<FeatureState>
 */
#[ListBased(itemClassName: FeatureState::class)]
final readonly class FeatureStates implements IteratorAggregate
{
    /**
     * @param list<FeatureState> $items
     */
    private function __construct(
        private array $items,
    ) {}

    /**
     * @param array<FeatureState> $items
     */
    public static function fromArray(array $items): self
    {
        return instantiate(self::class, $items);
    }

    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    public function get(FeatureId $featureId): FeatureState|null
    {
        foreach ($this->items as $state) {
            if ($state->featureId->equals($featureId)) {
                return $state;
            }
        }
        return null;
    }
}
