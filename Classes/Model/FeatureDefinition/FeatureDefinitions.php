<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureDefinition;

use Closure;
use IteratorAggregate;
use Traversable;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Types\Attributes\ListBased;

use function Wwwision\Types\instantiate;

/**
 * @implements IteratorAggregate<FeatureDefinition<FeatureOptions>>
 */
#[ListBased(itemClassName: FeatureDefinition::class)]
final readonly class FeatureDefinitions implements IteratorAggregate
{
    /**
     * @param list<FeatureDefinition<FeatureOptions>> $items
     */
    private function __construct(
        private array $items,
    ) {}

    /**
     * @param array<FeatureDefinition<FeatureOptions>> $items
     */
    public static function fromArray(array $items): self
    {
        return instantiate(self::class, $items);
    }

    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * @template T
     * @param Closure(FeatureDefinition<FeatureOptions>): T $callback
     * @return list<T>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * @return FeatureDefinition<FeatureOptions>|null
     */
    public function get(FeatureId $featureId): FeatureDefinition|null
    {
        foreach ($this->items as $definition) {
            if ($definition->id->equals($featureId)) {
                return $definition;
            }
        }
        return null;
    }
}
