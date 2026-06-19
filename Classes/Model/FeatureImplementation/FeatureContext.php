<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureImplementation;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Wwwision\Neos\Features\Model\CommonFeatures\YamlConfigurationFile;

/**
 * Passed as the first argument to every {@see FeatureImplementation} lifecycle method, giving access to the two
 * YAML files feature implementations commonly write overrides into, without each implementation needing its own
 * factory to construct them.
 */
final readonly class FeatureContext
{
    public function __construct(
        private YamlConfigurationFile $settingsFile,
        private YamlConfigurationFile $nodeTypesFile,
    ) {}

    public function settingsFile(): YamlConfigurationFile
    {
        return $this->settingsFile;
    }

    public function nodeTypesFile(): YamlConfigurationFile
    {
        return $this->nodeTypesFile;
    }

    /**
     * Makes one or more (otherwise abstract) node types available by setting `abstract: false` in the {@see nodeTypesFile()}.
     */
    public function activateNodeTypes(NodeTypeName|string ...$nodeTypeNames): void
    {
        $this->nodeTypesFile->setMany(
            array_values(array_map(static fn(NodeTypeName|string $n) => [[self::nodeTypeNameValue($n)], ['abstract' => false]], $nodeTypeNames)),
        );
    }

    /**
     * Reverts {@see activateNodeTypes()} by removing the corresponding overrides from the {@see nodeTypesFile()}.
     */
    public function deactivateNodeTypes(NodeTypeName|string ...$nodeTypeNames): void
    {
        $this->nodeTypesFile->unsetMany(
            array_values(array_map(static fn(NodeTypeName|string $n) => [self::nodeTypeNameValue($n)], $nodeTypeNames)),
        );
    }

    private static function nodeTypeNameValue(NodeTypeName|string $nodeTypeName): string
    {
        return $nodeTypeName instanceof NodeTypeName ? $nodeTypeName->getValue() : $nodeTypeName;
    }
}
