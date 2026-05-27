<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\Feature;

use IteratorAggregate;
use Traversable;
use Wwwision\Types\Attributes\ListBased;

use function Wwwision\Types\instantiate;

/**
 * @implements IteratorAggregate<Feature<FeatureOptions>>
 */
#[ListBased(itemClassName: Feature::class)]
final readonly class Features implements IteratorAggregate
{
    /**
     * @param list<Feature<FeatureOptions>> $items
     */
    private function __construct(
        private array $items,
    ) {}

    /**
     * @param array<Feature<FeatureOptions>> $items
     */
    public static function fromArray(array $items): self
    {
        return instantiate(self::class, $items);
    }

    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
