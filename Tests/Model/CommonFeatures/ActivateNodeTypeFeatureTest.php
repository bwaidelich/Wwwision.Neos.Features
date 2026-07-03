<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\CommonFeatures;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Wwwision\Neos\Features\Model\CommonFeatures\ActivateNodeTypeFeature;
use Wwwision\Neos\Features\Model\CommonFeatures\YamlConfigurationFile;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;

#[CoversClass(ActivateNodeTypeFeature::class)]
#[UsesClass(FeatureActivateResult::class)]
#[UsesClass(FeatureContext::class)]
#[UsesClass(FeatureDeactivateResult::class)]
#[UsesClass(YamlConfigurationFile::class)]
final class ActivateNodeTypeFeatureTest extends TestCase
{
    private string $nodeTypesPath;

    protected function setUp(): void
    {
        $this->nodeTypesPath = sys_get_temp_dir() . '/wwwision-neos-features-test-' . uniqid('', true) . '-NodeTypes.yaml';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->nodeTypesPath)) {
            unlink($this->nodeTypesPath);
        }
    }

    private function context(): FeatureContext
    {
        return new FeatureContext(new YamlConfigurationFile('/dev/null'), new YamlConfigurationFile($this->nodeTypesPath), []);
    }

    private static function feature(): ActivateNodeTypeFeature
    {
        return new ActivateNodeTypeFeature([NodeTypeName::fromString('Acme.Site:Document.One'), NodeTypeName::fromString('Acme.Site:Document.Two')]);
    }

    public function test_activate_marks_the_node_types_as_non_abstract_and_succeeds(): void
    {
        $result = self::feature()->activate($this->context());

        self::assertTrue($result->success);
        self::assertSame([
            'Acme.Site:Document.One' => ['abstract' => false],
            'Acme.Site:Document.Two' => ['abstract' => false],
        ], Yaml::parseFile($this->nodeTypesPath));
    }

    public function test_deactivate_removes_the_node_type_overrides_and_succeeds(): void
    {
        $feature = self::feature();
        $feature->activate($this->context());

        $result = $feature->deactivate($this->context());

        self::assertTrue($result->success);
        self::assertSame([], Yaml::parseFile($this->nodeTypesPath));
    }
}
