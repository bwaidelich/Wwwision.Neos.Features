<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\Feature;

use Closure;
use IteratorAggregate;
use Stringable;
use Traversable;
use Wwwision\Types\Attributes\ListBased;

use function Wwwision\Types\instantiate;

/**
 * A set of {@see FeatureId}s, e.g. the features a {@see \Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinition} depends on.
 *
 * @implements IteratorAggregate<FeatureId>
 */
#[ListBased(itemClassName: FeatureId::class)]
final readonly class FeatureIds implements IteratorAggregate, Stringable
{
    /**
     * @param list<FeatureId> $items
     */
    private function __construct(
        private array $items,
    ) {}

    /**
     * @param array<FeatureId|string> $items
     */
    public static function fromArray(array $items): self
    {
        return instantiate(self::class, $items);
    }

    public static function none(): self
    {
        return self::fromArray([]);
    }

    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function contains(FeatureId $featureId): bool
    {
        foreach ($this->items as $item) {
            if ($item->equals($featureId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @template T
     * @param Closure(FeatureId): T $callback
     * @return list<T>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * @return list<string>
     */
    public function toStringArray(): array
    {
        return $this->map(static fn(FeatureId $id): string => $id->value);
    }

    public function __toString(): string
    {
        return implode(', ', $this->toStringArray());
    }
}
