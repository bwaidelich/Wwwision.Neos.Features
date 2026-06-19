<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonFeatures;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;
use Wwwision\Neos\Features\Model\FeatureImplementation\OptionlessFeatureImplementation;

/**
 * A reusable optionless feature that makes one or more (otherwise abstract) document node types available by writing
 * overrides to the {@see FeatureContext}'s NodeTypes file. Reused across features for different node types via
 * {@see ActivateNodeTypeFeatureFactory}.
 */
final readonly class ActivateNodeTypeFeature implements OptionlessFeatureImplementation
{
    /**
     * @param list<NodeTypeName> $nodeTypeNames
     */
    public function __construct(
        public array $nodeTypeNames,
    ) {}

    public function activate(FeatureContext $context): FeatureActivateResult
    {
        $context->activateNodeTypes(...$this->nodeTypeNames);
        return FeatureActivateResult::success();
    }

    public function deactivate(FeatureContext $context): FeatureDeactivateResult
    {
        $context->deactivateNodeTypes(...$this->nodeTypeNames);
        return FeatureDeactivateResult::success();
    }
}
