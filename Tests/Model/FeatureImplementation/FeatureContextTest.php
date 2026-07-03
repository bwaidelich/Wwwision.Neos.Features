<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\FeatureImplementation;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Wwwision\Neos\Features\Model\CommonFeatures\YamlConfigurationFile;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;

#[CoversClass(FeatureContext::class)]
#[UsesClass(YamlConfigurationFile::class)]
final class FeatureContextTest extends TestCase
{
    private string $settingsPath;
    private string $nodeTypesPath;

    protected function setUp(): void
    {
        $prefix = sys_get_temp_dir() . '/wwwision-neos-features-test-' . uniqid('', true);
        $this->settingsPath = $prefix . '-Settings.yaml';
        $this->nodeTypesPath = $prefix . '-NodeTypes.yaml';
    }

    protected function tearDown(): void
    {
        foreach ([$this->settingsPath, $this->nodeTypesPath] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    private function context(): FeatureContext
    {
        return new FeatureContext(new YamlConfigurationFile($this->settingsPath), new YamlConfigurationFile($this->nodeTypesPath), []);
    }

    private function nodeTypesFileContents(): mixed
    {
        self::assertFileExists($this->nodeTypesPath);
        return Yaml::parseFile($this->nodeTypesPath);
    }

    public function test_settingsFile_returns_the_settings_file(): void
    {
        $this->context()->settingsFile()->set(['Acme', 'someSetting'], true);

        self::assertSame(['Acme' => ['someSetting' => true]], Yaml::parseFile($this->settingsPath));
        self::assertFileDoesNotExist($this->nodeTypesPath);
    }

    public function test_nodeTypesFile_returns_the_node_types_file(): void
    {
        $this->context()->nodeTypesFile()->set(['Acme.Site:Document'], ['superTypes' => ['Acme.Site:Mixin' => true]]);

        self::assertSame(['Acme.Site:Document' => ['superTypes' => ['Acme.Site:Mixin' => true]]], $this->nodeTypesFileContents());
        self::assertFileDoesNotExist($this->settingsPath);
    }

    public function test_activateNodeTypes_marks_all_given_node_types_as_non_abstract(): void
    {
        $this->context()->activateNodeTypes('Acme.Site:Document.One', NodeTypeName::fromString('Acme.Site:Document.Two'));

        self::assertSame([
            'Acme.Site:Document.One' => ['abstract' => false],
            'Acme.Site:Document.Two' => ['abstract' => false],
        ], $this->nodeTypesFileContents());
    }

    public function test_activateNodeTypes_preserves_unrelated_overrides(): void
    {
        $this->context()->nodeTypesFile()->set(['Acme.Site:Other', 'abstract'], false);

        $this->context()->activateNodeTypes('Acme.Site:Document');

        self::assertSame([
            'Acme.Site:Other' => ['abstract' => false],
            'Acme.Site:Document' => ['abstract' => false],
        ], $this->nodeTypesFileContents());
    }

    public function test_deactivateNodeTypes_removes_the_overrides_of_all_given_node_types(): void
    {
        $context = $this->context();
        $context->activateNodeTypes('Acme.Site:Document.One', 'Acme.Site:Document.Two', 'Acme.Site:Document.Three');

        $context->deactivateNodeTypes(NodeTypeName::fromString('Acme.Site:Document.One'), 'Acme.Site:Document.Three');

        self::assertSame(['Acme.Site:Document.Two' => ['abstract' => false]], $this->nodeTypesFileContents());
    }

    public function test_deactivateNodeTypes_leaves_an_empty_map_when_no_overrides_remain(): void
    {
        $context = $this->context();
        $context->activateNodeTypes('Acme.Site:Document');

        $context->deactivateNodeTypes('Acme.Site:Document');

        self::assertSame([], $this->nodeTypesFileContents());
    }
}
