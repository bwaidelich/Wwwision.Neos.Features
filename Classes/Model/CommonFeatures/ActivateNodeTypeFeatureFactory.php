<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonFeatures;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Utility\Files;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementation;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementationFactory;

/**
 * Builds an {@see ActivateNodeTypeFeature} from factory options.
 *
 * Required option:
 *   nodeType: 'Vendor.Package:MyDocumentType'
 *
 * Optional options (mutually exclusive):
 *   filePath: '/absolute/path/to/NodeTypes.Features.yaml'  – fully custom file path
 *   fileName: 'NodeTypes.Custom.yaml'                      – filename inside $defaultConfigurationPath
 *
 * When neither is given, defaults to `<defaultConfigurationPath>/NodeTypes.Features.yaml`.
 *
 * $defaultConfigurationPath is injected via Flow's object framework (defaults to FLOW_PATH_CONFIGURATION).
 * Override it globally in Objects.yaml:
 *   Wwwision\Neos\Features\Model\CommonFeatures\ActivateNodeTypeFeatureFactory:
 *     arguments:
 *       1:
 *         value: '%FLOW_PATH_CONFIGURATION%'
 */
final readonly class ActivateNodeTypeFeatureFactory implements FeatureImplementationFactory
{
    public function __construct(
        private string $defaultConfigurationPath = FLOW_PATH_CONFIGURATION, // @phpstan-ignore constant.notFound
    ) {}

    public function create(array $options): FeatureImplementation
    {
        Assert::keyExists($options, 'nodeType', 'ActivateNodeTypeFeature requires a "nodeType" option');
        Assert::string($options['nodeType'], 'ActivateNodeTypeFeature option "nodeType" must be a string, given: %s');
        Assert::false(
            isset($options['filePath']) && isset($options['fileName']),
            'ActivateNodeTypeFeature options "filePath" and "fileName" are mutually exclusive',
        );

        if (isset($options['filePath'])) {
            Assert::string($options['filePath'], 'ActivateNodeTypeFeature option "filePath" must be a string, given: %s');
            $configFile = new YamlConfigurationFile($options['filePath']);
        } else {
            $fileName = $options['fileName'] ?? 'NodeTypes.Features.yaml';
            Assert::string($fileName, 'ActivateNodeTypeFeature option "fileName" must be a string, given: %s');
            $configFile = new YamlConfigurationFile(Files::concatenatePaths([$this->defaultConfigurationPath, $fileName]));
        }

        return new ActivateNodeTypeFeature(NodeTypeName::fromString($options['nodeType']), $configFile);
    }
}
