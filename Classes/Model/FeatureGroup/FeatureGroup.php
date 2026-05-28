<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureGroup;

/**
 * A named bucket used to organise {@see \Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinition}s for
 * presentation in the backend module. Groups carry no behaviour and do not affect activation.
 */
final readonly class FeatureGroup
{
    private function __construct(
        public FeatureGroupId $id,
        public FeatureGroupName $name,
        public FeatureGroupDescription $description,
        public FeatureGroupIcon|null $icon,
    ) {}

    public static function create(
        FeatureGroupId|string $id,
        FeatureGroupName|string $name,
        FeatureGroupDescription|string|null $description = null,
        FeatureGroupIcon|string|null $icon = null,
    ): self {
        if (is_string($id)) {
            $id = FeatureGroupId::fromString($id);
        }
        if (is_string($name)) {
            $name = FeatureGroupName::fromString($name);
        }
        if ($description === null) {
            $description = FeatureGroupDescription::fromString('');
        } elseif (is_string($description)) {
            $description = FeatureGroupDescription::fromString($description);
        }
        if (is_string($icon)) {
            $icon = FeatureGroupIcon::fromString($icon);
        }
        return new self($id, $name, $description, $icon);
    }
}
