<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonFeatures;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementation;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementationFactory;

/**
 * Builds an {@see ActivateNodeTypeFeature} from a `nodeType` factory option, so one implementation can toggle
 * different document node types.
 */
final readonly class ActivateNodeTypeFeatureFactory implements FeatureImplementationFactory
{
    public function create(array $options): FeatureImplementation
    {
        Assert::keyExists($options, 'nodeType', 'ActivateNodeTypeFeature requires a "nodeType" option');
        Assert::string($options['nodeType'], 'ActivateNodeTypeFeature option "nodeType" must be a string, given: %s');
        return new ActivateNodeTypeFeature(NodeTypeName::fromString($options['nodeType']));
    }
}
