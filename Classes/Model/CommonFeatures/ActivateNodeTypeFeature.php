<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonFeatures;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\FeatureImplementation\OptionlessFeatureImplementation;

/**
 * A reusable optionless feature that makes one or more (otherwise abstract) document node types available by writing
 * overrides to a YAML configuration file. Reused across features for different node types via {@see ActivateNodeTypeFeatureFactory}.
 */
final readonly class ActivateNodeTypeFeature implements OptionlessFeatureImplementation
{
    /**
     * @param list<NodeTypeName> $nodeTypeNames
     */
    public function __construct(
        public array $nodeTypeNames,
        private YamlConfigurationFile $configFile,
    ) {}

    public function activate(): FeatureActivateResult
    {
        $this->configFile->setMany(
            array_map(static fn(NodeTypeName $n) => [[$n->getValue()], ['abstract' => false]], $this->nodeTypeNames),
        );
        return FeatureActivateResult::success();
    }

    public function deactivate(): FeatureDeactivateResult
    {
        $this->configFile->unsetMany(
            array_map(static fn(NodeTypeName $n) => [$n->getValue()], $this->nodeTypeNames),
        );
        return FeatureDeactivateResult::success();
    }
}
