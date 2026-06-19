<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonFeatures;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementation;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementationFactory;

/**
 * Builds an {@see ActivateNodeTypeFeature} from factory options.
 *
 * Required option (mutually exclusive, at least one must be set):
 *   nodeType:  'Vendor.Package:MyDocumentType'                        – single node type
 *   nodeTypes: ['Vendor.Package:Foo', 'Vendor.Package:Bar']           – multiple node types
 *
 * The node type overrides are always written to the {@see \Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext}'s
 * NodeTypes file (path configured globally via the `Wwwision.Neos.Features.configurationFiles.nodeTypes.path` setting).
 */
final readonly class ActivateNodeTypeFeatureFactory implements FeatureImplementationFactory
{
    public function create(array $options): FeatureImplementation
    {
        Assert::false(
            isset($options['nodeType']) && isset($options['nodeTypes']),
            'ActivateNodeTypeFeature options "nodeType" and "nodeTypes" are mutually exclusive',
        );
        Assert::true(
            isset($options['nodeType']) || isset($options['nodeTypes']),
            'ActivateNodeTypeFeature requires either a "nodeType" or a "nodeTypes" option',
        );

        if (isset($options['nodeType'])) {
            Assert::string($options['nodeType'], 'ActivateNodeTypeFeature option "nodeType" must be a string, given: %s');
            $nodeTypeNames = [NodeTypeName::fromString($options['nodeType'])];
        } else {
            Assert::isArray($options['nodeTypes'], 'ActivateNodeTypeFeature option "nodeTypes" must be an array, given: %s');
            Assert::allString($options['nodeTypes'], 'ActivateNodeTypeFeature option "nodeTypes" must be a list of strings, given: %s');
            $nodeTypeNames = array_map(NodeTypeName::fromString(...), $options['nodeTypes']);
        }

        return new ActivateNodeTypeFeature($nodeTypeNames);
    }
}
