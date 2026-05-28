<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureGroup;

use IteratorAggregate;
use Traversable;
use Wwwision\Types\Attributes\ListBased;

use function Wwwision\Types\instantiate;

/**
 * @implements IteratorAggregate<FeatureGroup>
 */
#[ListBased(itemClassName: FeatureGroup::class)]
final readonly class FeatureGroups implements IteratorAggregate
{
    /**
     * @param list<FeatureGroup> $items
     */
    private function __construct(
        private array $items,
    ) {}

    /**
     * @param array<FeatureGroup> $items
     */
    public static function fromArray(array $items): self
    {
        return instantiate(self::class, $items);
    }

    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    public function get(FeatureGroupId $id): FeatureGroup|null
    {
        foreach ($this->items as $group) {
            if ($group->id->equals($id)) {
                return $group;
            }
        }
        return null;
    }

    public function has(FeatureGroupId $id): bool
    {
        return $this->get($id) !== null;
    }
}
