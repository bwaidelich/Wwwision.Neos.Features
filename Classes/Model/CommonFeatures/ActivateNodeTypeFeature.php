<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonFeatures;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Utility\Files;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\FeatureImplementation\OptionlessFeatureImplementation;

/**
 * A reusable optionless feature that makes a (otherwise abstract) document node type available by writing an override
 * to `NodeTypes.Features.yaml`. Reused across features for different node types via {@see ActivateNodeTypeFeatureFactory}.
 */
final readonly class ActivateNodeTypeFeature implements OptionlessFeatureImplementation
{
    public function __construct(
        public NodeTypeName $nodeTypeName,
    ) {}

    public function activate(): FeatureActivateResult
    {
        $nodeTypes = $this->loadNodeTypeOverrides();
        $nodeTypes[$this->nodeTypeName->getValue()] = ['abstract' => false];
        $this->storeNodeTypeOverrides($nodeTypes);

        return FeatureActivateResult::success();
    }

    public function deactivate(): FeatureDeactivateResult
    {
        $nodeTypes = $this->loadNodeTypeOverrides();
        unset($nodeTypes[$this->nodeTypeName->getValue()]);
        $this->storeNodeTypeOverrides($nodeTypes);

        return FeatureDeactivateResult::success();
    }

    /**
     * @return array<string, mixed>
     */
    private function loadNodeTypeOverrides(): array
    {
        $nodeTypesFile = Files::concatenatePaths([FLOW_PATH_CONFIGURATION, 'NodeTypes.Features.yaml']); // @phpstan-ignore constant.notFound
        if (!is_file($nodeTypesFile)) {
            return [];
        }
        $nodeTypes = Yaml::parseFile($nodeTypesFile) ?? [];
        Assert::isMap($nodeTypes, sprintf('Expected a map in "%s", given: %%s', $nodeTypesFile));
        return $nodeTypes;
    }

    /**
     * @param array<string, mixed> $nodeTypes
     */
    private function storeNodeTypeOverrides(array $nodeTypes): void
    {
        $nodeTypesFile = Files::concatenatePaths([FLOW_PATH_CONFIGURATION, 'NodeTypes.Features.yaml']); // @phpstan-ignore constant.notFound
        file_put_contents($nodeTypesFile, Yaml::dump($nodeTypes, 10, 2));
    }
}
