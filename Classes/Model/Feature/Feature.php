<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\Feature;

use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDescription;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureIcon;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureName;
use Wwwision\Types\Parser;
use Wwwision\Types\Schema\ShapeSchema;

/**
 * @template TOptions of FeatureOptions
 */
final readonly class Feature
{
    /**
     * @param class-string<TOptions> $optionsClassName
     * @param TOptions|null $options
     */
    public function __construct(
        public FeatureId $id,
        public FeatureName $name,
        public FeatureDescription $description,
        public FeatureIcon|null $icon,
        private string $optionsClassName,
        public bool $active,
        public FeatureOptions|null $options,
    ) {}

    public function getOptionsSchema(): ShapeSchema
    {
        $schema = Parser::getSchema($this->optionsClassName);
        Assert::isInstanceOf($schema, ShapeSchema::class);
        return $schema;
    }
}
