<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\Feature;

use LogicException;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDescription;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureIcon;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureName;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroupId;
use Wwwision\Types\Normalizer\Normalizer;
use Wwwision\Types\Parser;
use Wwwision\Types\Schema\ShapeSchema;

/**
 * @template TOptions of FeatureOptions
 */
final readonly class Feature
{
    /**
     * @param class-string<TOptions>|null $optionsClassName null for optionless features
     * @param TOptions|null $options
     * @param FeatureIds $dependsOn the features this one requires to be active
     * @param FeatureIds $unmetDependencies subset of {@see $dependsOn} that is currently inactive (activation is blocked while non-empty)
     * @param FeatureIds $activeDependents currently active features that require this one (deactivation is blocked while non-empty)
     */
    public function __construct(
        public FeatureId $id,
        public FeatureName $name,
        public FeatureDescription $description,
        public FeatureIcon|null $icon,
        private string|null $optionsClassName,
        public bool $active,
        public FeatureOptions|null $options,
        public FeatureGroupId|null $group,
        public FeatureIds $dependsOn,
        public FeatureIds $unmetDependencies,
        public FeatureIds $activeDependents,
    ) {}

    /**
     * Whether this feature takes options (and, in turn, can have its options updated).
     */
    public function hasOptions(): bool
    {
        return $this->optionsClassName !== null;
    }

    public function getOptionsSchema(): ShapeSchema
    {
        if ($this->optionsClassName === null) {
            throw new LogicException(sprintf('Feature "%s" has no options', $this->id->value), 1780682590);
        }
        $schema = Parser::getSchema($this->optionsClassName);
        Assert::isInstanceOf($schema, ShapeSchema::class);
        return $schema;
    }

    public function getNormalizedOptions(): mixed
    {
        if ($this->options === null) {
            return null;
        }
        return (new Normalizer())->normalize($this->options);
    }

    /**
     * Whether the feature may currently be activated (it is inactive and all its dependencies are active).
     */
    public function isActivatable(): bool
    {
        return !$this->active && $this->unmetDependencies->isEmpty();
    }

    /**
     * Whether the feature may currently be deactivated (it is active and no active feature still requires it).
     */
    public function isDeactivatable(): bool
    {
        return $this->active && $this->activeDependents->isEmpty();
    }
}
