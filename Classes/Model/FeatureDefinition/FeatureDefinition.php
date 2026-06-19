<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureDefinition;

use Closure;
use LogicException;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\FeatureIds;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\Feature\FeatureUpdateOptionsResult;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroupId;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;
use Wwwision\Types\Options;
use Wwwision\Types\Parser;
use Wwwision\Types\Schema\ShapeSchema;

/**
 * @template TOptions of FeatureOptions
 */
final readonly class FeatureDefinition
{
    /**
     * @param class-string<TOptions>|null $optionsClassName null for optionless features
     * @param Closure(FeatureContext, TOptions): FeatureActivateResult|Closure(FeatureContext): FeatureActivateResult $onActivate
     * @param Closure(FeatureContext, TOptions, TOptions): FeatureUpdateOptionsResult|null $onUpdateOptions null for optionless features
     * @param Closure(FeatureContext, TOptions): FeatureDeactivateResult|Closure(FeatureContext): FeatureDeactivateResult $onDeactivate
     */
    private function __construct(
        public FeatureId $id,
        public FeatureName $name,
        public string|null $optionsClassName,
        public Closure $onActivate,
        public Closure|null $onUpdateOptions,
        public Closure $onDeactivate,
        public FeatureDescription $description,
        public FeatureIcon|null $icon,
        public FeatureIds $dependsOn,
        public FeatureGroupId|null $group,
    ) {}

    /**
     * Creates a definition for a configurable feature (one that takes typed options).
     *
     * @template TO of FeatureOptions
     * @param class-string<TO> $optionsClassName
     * @param callable(FeatureContext, TO): FeatureActivateResult $onActivate
     * @param callable(FeatureContext, TO, TO): FeatureUpdateOptionsResult $onUpdateOptions
     * @param callable(FeatureContext, TO): FeatureDeactivateResult $onDeactivate
     * @param FeatureIds|array<FeatureId|string>|null $dependsOn
     * @return self<TO>
     */
    public static function create(
        FeatureId|string $id,
        FeatureName|string $name,
        string $optionsClassName,
        callable $onActivate,
        callable $onUpdateOptions,
        callable $onDeactivate,
        FeatureDescription|string|null $description = null,
        FeatureIcon|string|null $icon = null,
        FeatureIds|array|null $dependsOn = null,
        FeatureGroupId|string|null $group = null,
    ): self {
        return new self(
            self::coerceId($id),
            self::coerceName($name),
            $optionsClassName,
            $onActivate(...),
            $onUpdateOptions(...),
            $onDeactivate(...),
            self::coerceDescription($description),
            self::coerceIcon($icon),
            self::coerceDependsOn($dependsOn),
            self::coerceGroup($group),
        );
    }

    /**
     * Creates a definition for an optionless feature (one that takes no options and cannot have its options updated).
     *
     * @param callable(FeatureContext): FeatureActivateResult $onActivate
     * @param callable(FeatureContext): FeatureDeactivateResult $onDeactivate
     * @param FeatureIds|array<FeatureId|string>|null $dependsOn
     * @return self<FeatureOptions>
     */
    public static function createOptionless(
        FeatureId|string $id,
        FeatureName|string $name,
        callable $onActivate,
        callable $onDeactivate,
        FeatureDescription|string|null $description = null,
        FeatureIcon|string|null $icon = null,
        FeatureIds|array|null $dependsOn = null,
        FeatureGroupId|string|null $group = null,
    ): self {
        return new self(
            self::coerceId($id),
            self::coerceName($name),
            null,
            $onActivate(...),
            null,
            $onDeactivate(...),
            self::coerceDescription($description),
            self::coerceIcon($icon),
            self::coerceDependsOn($dependsOn),
            self::coerceGroup($group),
        );
    }

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
            throw new LogicException(sprintf('Feature "%s" has no options', $this->id->value), 1780682589);
        }
        $schema = Parser::getSchema($this->optionsClassName);
        Assert::isInstanceOf($schema, ShapeSchema::class);
        return $schema;
    }

    /**
     * @param array<string, mixed> $options
     * @return TOptions
     */
    public function parseOptions(array $options): FeatureOptions
    {
        $parsedOptions = $this->getOptionsSchema()->instantiate($options, Options::create(ignoreUnrecognizedKeys: true));
        Assert::isInstanceOf($parsedOptions, FeatureOptions::class);
        return $parsedOptions; // @phpstan-ignore return.type
    }

    private static function coerceId(FeatureId|string $id): FeatureId
    {
        return is_string($id) ? FeatureId::fromString($id) : $id;
    }

    private static function coerceName(FeatureName|string $name): FeatureName
    {
        return is_string($name) ? FeatureName::fromString($name) : $name;
    }

    private static function coerceDescription(FeatureDescription|string|null $description): FeatureDescription
    {
        if ($description === null) {
            return FeatureDescription::fromString('');
        }
        return is_string($description) ? FeatureDescription::fromString($description) : $description;
    }

    private static function coerceIcon(FeatureIcon|string|null $icon): FeatureIcon|null
    {
        return is_string($icon) ? FeatureIcon::fromString($icon) : $icon;
    }

    /**
     * @param FeatureIds|array<FeatureId|string>|null $dependsOn
     */
    private static function coerceDependsOn(FeatureIds|array|null $dependsOn): FeatureIds
    {
        if ($dependsOn === null) {
            return FeatureIds::none();
        }
        return is_array($dependsOn) ? FeatureIds::fromArray($dependsOn) : $dependsOn;
    }

    private static function coerceGroup(FeatureGroupId|string|null $group): FeatureGroupId|null
    {
        return is_string($group) ? FeatureGroupId::fromString($group) : $group;
    }
}
