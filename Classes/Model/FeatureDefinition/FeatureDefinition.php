<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureDefinition;

use Closure;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\FeatureIds;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroupId;
use Wwwision\Types\Options;
use Wwwision\Types\Parser;
use Wwwision\Types\Schema\ShapeSchema;

/**
 * @template TOptions of FeatureOptions
 */
final readonly class FeatureDefinition
{
    /**
     * @param class-string<TOptions> $optionsClassName
     * @param Closure(TOptions): FeatureActivateResult $onActivate
     * @param Closure(): FeatureDeactivateResult $onDeactivate
     */
    private function __construct(
        public FeatureId $id,
        public FeatureName $name,
        public string $optionsClassName,
        public Closure $onActivate,
        public Closure $onDeactivate,
        public FeatureDescription $description,
        public FeatureIcon|null $icon,
        public FeatureIds $dependsOn,
        public FeatureGroupId|null $group,
    ) {}

    /**
     * @template TO of FeatureOptions
     * @param class-string<TO> $optionsClassName
     * @param callable(TO): FeatureActivateResult $onActivate
     * @param callable(): FeatureDeactivateResult $onDeactivate
     * @param FeatureIds|array<FeatureId|string>|null $dependsOn
     * @return self<TO>
     */
    public static function create(
        FeatureId|string $id,
        FeatureName|string $name,
        string $optionsClassName,
        callable $onActivate,
        callable $onDeactivate,
        FeatureDescription|string|null $description = null,
        FeatureIcon|string|null $icon = null,
        FeatureIds|array|null $dependsOn = null,
        FeatureGroupId|string|null $group = null,
    ): self {
        if (is_string($id)) {
            $id = FeatureId::fromString($id);
        }
        if (is_string($name)) {
            $name = FeatureName::fromString($name);
        }
        if ($description === null) {
            $description = FeatureDescription::fromString('');
        } elseif (is_string($description)) {
            $description = FeatureDescription::fromString($description);
        }
        if (is_string($icon)) {
            $icon = FeatureIcon::fromString($icon);
        }
        if ($dependsOn === null) {
            $dependsOn = FeatureIds::none();
        } elseif (is_array($dependsOn)) {
            $dependsOn = FeatureIds::fromArray($dependsOn);
        }
        if (is_string($group)) {
            $group = FeatureGroupId::fromString($group);
        }
        return new self($id, $name, $optionsClassName, $onActivate(...), $onDeactivate(...), $description, $icon, $dependsOn, $group);
    }

    public function getOptionsSchema(): ShapeSchema
    {
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
}
