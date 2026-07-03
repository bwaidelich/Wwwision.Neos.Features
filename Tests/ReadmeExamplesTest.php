<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests;

use Neos\Flow\Annotations\Proxy;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use ParseError;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Yaml\Yaml;
use Wwwision\Neos\Features\Adapter\FeatureProviderFromSettings;
use Wwwision\Neos\Features\Model\CommonFeatures\YamlConfigurationFile;
use Wwwision\Neos\Features\Model\FeatureImplementation\ConfigurableFeatureImplementation;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementationFactory;
use Wwwision\Neos\Features\Model\FeatureImplementation\OptionlessFeatureImplementation;

/**
 * Verifies the code examples in the README:
 * - all YAML examples must be parseable, feature declaration examples must produce a valid configuration
 * - all PHP examples must be valid; complete examples (starting with "<?php") are evaluated and exercised
 */
#[CoversNothing]
final class ReadmeExamplesTest extends TestCase
{
    private const string EXAMPLE_NAMESPACE = 'Some\Package\Features';

    private string $settingsPath;
    private string $nodeTypesPath;

    protected function setUp(): void
    {
        $prefix = sys_get_temp_dir() . '/wwwision-neos-features-readme-test-' . uniqid('', true);
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

    /**
     * @return list<string>
     */
    private static function codeBlocks(string $language): array
    {
        $readme = file_get_contents(__DIR__ . '/../README.md');
        self::assertIsString($readme);
        preg_match_all('/^```(\w+)\n(.*?)^```$/sm', $readme, $matches, PREG_SET_ORDER);
        $blocks = [];
        foreach ($matches as $match) {
            if ($match[1] === $language) {
                $blocks[] = $match[2];
            }
        }
        return $blocks;
    }

    /**
     * Evaluates all complete PHP examples once, defining the example classes in the {@see self::EXAMPLE_NAMESPACE}.
     */
    private static function loadPhpExampleClasses(): void
    {
        if (class_exists(self::EXAMPLE_NAMESPACE . '\DarkModeFeature', false)) {
            return;
        }
        foreach (self::codeBlocks('php') as $code) {
            if (str_starts_with($code, '<?php')) {
                eval(substr($code, strlen('<?php')));
            }
        }
    }

    private function featureContext(): FeatureContext
    {
        return new FeatureContext(new YamlConfigurationFile($this->settingsPath), new YamlConfigurationFile($this->nodeTypesPath), []);
    }

    public function test_all_yaml_examples_are_parseable(): void
    {
        $blocks = self::codeBlocks('yaml');
        self::assertNotSame([], $blocks);
        foreach ($blocks as $code) {
            self::assertIsArray(Yaml::parse($code));
        }
    }

    public function test_all_php_example_snippets_have_valid_syntax(): void
    {
        $blocks = self::codeBlocks('php');
        self::assertNotSame([], $blocks);
        foreach ($blocks as $code) {
            if (str_starts_with($code, '<?php')) {
                continue; // complete examples are evaluated in the tests below
            }
            try {
                token_get_all('<?php ' . $code, TOKEN_PARSE);
            } catch (ParseError $e) {
                self::fail(sprintf('PHP example snippet contains a syntax error: %s (in: %s)', $e->getMessage(), $code));
            }
        }
    }

    public function test_the_php_examples_define_the_documented_classes(): void
    {
        self::loadPhpExampleClasses();

        self::assertTrue(is_subclass_of(self::EXAMPLE_NAMESPACE . '\DarkModeFeature', OptionlessFeatureImplementation::class));
        self::assertTrue(is_subclass_of(self::EXAMPLE_NAMESPACE . '\MaintenanceModeFeature', ConfigurableFeatureImplementation::class));
        self::assertTrue(is_subclass_of(self::EXAMPLE_NAMESPACE . '\RedirectFeatureFactory', FeatureImplementationFactory::class));
    }

    public function test_the_options_class_example_disables_the_flow_proxy(): void
    {
        self::loadPhpExampleClasses();

        $attributes = new ReflectionClass(self::EXAMPLE_NAMESPACE . '\MaintenanceModeOptions')->getAttributes(Proxy::class);
        self::assertCount(1, $attributes, 'Expected the options class example to carry a #[Flow\Proxy] attribute');
        self::assertFalse($attributes[0]->newInstance()->enabled);
    }

    public function test_the_feature_declaration_examples_produce_a_valid_configuration(): void
    {
        self::loadPhpExampleClasses();

        $validated = 0;
        foreach (self::codeBlocks('yaml') as $code) {
            $settings = Yaml::parse($code)['Wwwision']['Neos']['Features'] ?? null;
            if (!is_array($settings) || !isset($settings['features'])) {
                continue;
            }
            $objectManager = $this->createStub(ObjectManagerInterface::class);
            $objectManager->method('get')->willReturnCallback(static fn(string $objectName): object => new $objectName());
            $provider = new FeatureProviderFromSettings($settings['features'], $settings['featureGroups'] ?? [], $objectManager);

            self::assertNotSame([], iterator_to_array($provider->getFeatureDefinitions()));
            $provider->getFeatureGroups();
            $validated++;
        }
        self::assertSame(2, $validated, 'Expected exactly two feature declaration examples in the README');
    }

    public function test_the_optionless_feature_example_can_be_activated_and_deactivated(): void
    {
        self::loadPhpExampleClasses();
        $featureClassName = self::EXAMPLE_NAMESPACE . '\DarkModeFeature';
        $feature = new $featureClassName();
        self::assertInstanceOf(OptionlessFeatureImplementation::class, $feature);

        self::assertTrue($feature->activate($this->featureContext())->success);
        self::assertSame(['Some' => ['Package' => ['darkMode' => ['enabled' => true]]]], Yaml::parseFile($this->settingsPath));

        self::assertTrue($feature->deactivate($this->featureContext())->success);
        self::assertSame([], Yaml::parseFile($this->settingsPath));
    }

    public function test_the_configurable_feature_example_can_be_activated_updated_and_deactivated(): void
    {
        self::loadPhpExampleClasses();
        $featureClassName = self::EXAMPLE_NAMESPACE . '\MaintenanceModeFeature';
        $optionsClassName = self::EXAMPLE_NAMESPACE . '\MaintenanceModeOptions';
        $feature = new $featureClassName();
        self::assertInstanceOf(ConfigurableFeatureImplementation::class, $feature);
        self::assertSame($optionsClassName, $feature::optionsClassName());

        $feature->activate($this->featureContext(), new $optionsClassName('We will be back shortly'));
        self::assertSame(['Some' => ['Package' => ['maintenance' => [
            'enabled' => true,
            'message' => 'We will be back shortly',
            'allowBackendUsers' => true,
        ]]]], Yaml::parseFile($this->settingsPath));

        $feature->updateOptions($this->featureContext(), new $optionsClassName('We will be back shortly'), new $optionsClassName('Back tomorrow', false));
        self::assertSame(['Some' => ['Package' => ['maintenance' => [
            'enabled' => true,
            'message' => 'Back tomorrow',
            'allowBackendUsers' => false,
        ]]]], Yaml::parseFile($this->settingsPath));

        $feature->deactivate($this->featureContext(), new $optionsClassName('Back tomorrow', false));
        self::assertSame([], Yaml::parseFile($this->settingsPath));
    }
}
