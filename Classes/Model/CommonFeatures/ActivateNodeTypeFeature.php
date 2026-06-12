<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonFeatures;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\FeatureImplementation\OptionlessFeatureImplementation;

/**
 * A reusable optionless feature that makes a (otherwise abstract) document node type available by writing an override
 * to a YAML configuration file. Reused across features for different node types via {@see ActivateNodeTypeFeatureFactory}.
 */
final readonly class ActivateNodeTypeFeature implements OptionlessFeatureImplementation
{
    public function __construct(
        public NodeTypeName $nodeTypeName,
        private YamlConfigurationFile $configFile,
    ) {}

    public function activate(): FeatureActivateResult
    {
        $this->configFile->set([$this->nodeTypeName->getValue()], ['abstract' => false]);
        return FeatureActivateResult::success();
    }

    public function deactivate(): FeatureDeactivateResult
    {
        $this->configFile->unset([$this->nodeTypeName->getValue()]);
        return FeatureDeactivateResult::success();
    }
}
