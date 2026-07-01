<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\FeatureDefinition;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\Feature\FeatureUpdateOptionsResult;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinition;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDescription;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureIcon;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureName;
use Wwwision\Neos\Features\Model\CommonFeatures\YamlConfigurationFile;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;
use Wwwision\Neos\Features\Tests\Fixtures\SampleFeatureOptions;

#[CoversClass(FeatureDefinition::class)]
final class FeatureDefinitionTest extends TestCase
{
    private static function context(): FeatureContext
    {
        return new FeatureContext(new YamlConfigurationFile('/dev/null'), new YamlConfigurationFile('/dev/null'), []);
    }

    /**
     * @return callable(FeatureContext, FeatureOptions): FeatureActivateResult
     */
    private static function noopActivate(): callable
    {
        return static fn(FeatureContext $context, FeatureOptions $options): FeatureActivateResult => FeatureActivateResult::success();
    }

    /**
     * @return callable(FeatureContext, FeatureOptions, FeatureOptions): FeatureUpdateOptionsResult
     */
    private static function noopUpdateOptions(): callable
    {
        return static fn(FeatureContext $context, FeatureOptions $previous, FeatureOptions $new): FeatureUpdateOptionsResult => FeatureUpdateOptionsResult::success();
    }

    /**
     * @return callable(FeatureContext, FeatureOptions): FeatureDeactivateResult
     */
    private static function noopDeactivate(): callable
    {
        return static fn(FeatureContext $context, FeatureOptions $options): FeatureDeactivateResult => FeatureDeactivateResult::success();
    }

    public function test_create_coerces_string_arguments_to_value_objects(): void
    {
        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: SampleFeatureOptions::class,
            onActivate: self::noopActivate(),
            onUpdateOptions: self::noopUpdateOptions(),
            onDeactivate: self::noopDeactivate(),
            description: 'A description',
            icon: 'star',
        );

        self::assertTrue($definition->id->equals(FeatureId::fromString('my-feature')));
        self::assertInstanceOf(FeatureName::class, $definition->name);
        self::assertSame('My Feature', (string) $definition->name);
        self::assertInstanceOf(FeatureDescription::class, $definition->description);
        self::assertSame('A description', (string) $definition->description);
        self::assertInstanceOf(FeatureIcon::class, $definition->icon);
        self::assertSame('star', (string) $definition->icon);
    }

    public function test_create_accepts_already_built_value_objects(): void
    {
        $definition = FeatureDefinition::create(
            id: FeatureId::fromString('my-feature'),
            name: FeatureName::fromString('My Feature'),
            optionsClassName: SampleFeatureOptions::class,
            onActivate: self::noopActivate(),
            onUpdateOptions: self::noopUpdateOptions(),
            onDeactivate: self::noopDeactivate(),
            description: FeatureDescription::fromString('desc'),
            icon: FeatureIcon::fromString('cog'),
        );

        self::assertSame('my-feature', $definition->id->value);
        self::assertSame('desc', (string) $definition->description);
        self::assertSame('cog', (string) $definition->icon);
    }

    public function test_create_defaults_description_to_an_empty_string(): void
    {
        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: SampleFeatureOptions::class,
            onActivate: self::noopActivate(),
            onUpdateOptions: self::noopUpdateOptions(),
            onDeactivate: self::noopDeactivate(),
        );

        self::assertSame('', (string) $definition->description);
    }

    public function test_create_defaults_icon_to_null(): void
    {
        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: SampleFeatureOptions::class,
            onActivate: self::noopActivate(),
            onUpdateOptions: self::noopUpdateOptions(),
            onDeactivate: self::noopDeactivate(),
        );

        self::assertNull($definition->icon);
    }

    public function test_the_activate_update_and_deactivate_callbacks_are_wired_up(): void
    {
        $activatedWith = null;
        $updatedWith = null;
        $deactivatedWith = null;

        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: SampleFeatureOptions::class,
            onActivate: function (FeatureContext $context, FeatureOptions $options) use (&$activatedWith): FeatureActivateResult {
                $activatedWith = $options;
                return FeatureActivateResult::success();
            },
            onUpdateOptions: function (FeatureContext $context, FeatureOptions $previous, FeatureOptions $new) use (&$updatedWith): FeatureUpdateOptionsResult {
                $updatedWith = [$previous, $new];
                return FeatureUpdateOptionsResult::success();
            },
            onDeactivate: function (FeatureContext $context, FeatureOptions $options) use (&$deactivatedWith): FeatureDeactivateResult {
                $deactivatedWith = $options;
                return FeatureDeactivateResult::success();
            },
        );

        $options = new SampleFeatureOptions('hi');
        $newOptions = new SampleFeatureOptions('bye');
        $context = self::context();
        ($definition->onActivate)($context, $options);
        ($definition->onUpdateOptions)($context, $options, $newOptions);
        ($definition->onDeactivate)($context, $options);

        self::assertSame($options, $activatedWith);
        self::assertSame([$options, $newOptions], $updatedWith);
        self::assertSame($options, $deactivatedWith);
    }

    public function test_parseOptions_instantiates_the_options_class(): void
    {
        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: SampleFeatureOptions::class,
            onActivate: self::noopActivate(),
            onUpdateOptions: self::noopUpdateOptions(),
            onDeactivate: self::noopDeactivate(),
        );

        $options = $definition->parseOptions(['message' => 'hello', 'threshold' => 7]);

        self::assertInstanceOf(SampleFeatureOptions::class, $options);
        self::assertSame('hello', $options->message);
        self::assertSame(7, $options->threshold);
    }

    public function test_parseOptions_ignores_unrecognized_keys(): void
    {
        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: SampleFeatureOptions::class,
            onActivate: self::noopActivate(),
            onUpdateOptions: self::noopUpdateOptions(),
            onDeactivate: self::noopDeactivate(),
        );

        $options = $definition->parseOptions(['message' => 'hello', 'unknown' => 'ignored']);

        self::assertInstanceOf(SampleFeatureOptions::class, $options);
        self::assertSame('hello', $options->message);
    }

    public function test_create_marks_the_definition_as_having_options(): void
    {
        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: SampleFeatureOptions::class,
            onActivate: self::noopActivate(),
            onUpdateOptions: self::noopUpdateOptions(),
            onDeactivate: self::noopDeactivate(),
        );

        self::assertTrue($definition->hasOptions());
        self::assertSame(SampleFeatureOptions::class, $definition->optionsClassName);
    }

    public function test_createOptionless_builds_a_definition_with_no_options(): void
    {
        $activated = false;
        $deactivated = false;

        $definition = FeatureDefinition::createOptionless(
            id: 'my-feature',
            name: 'My Feature',
            onActivate: function (FeatureContext $context) use (&$activated): FeatureActivateResult {
                $activated = true;
                return FeatureActivateResult::success();
            },
            onDeactivate: function (FeatureContext $context) use (&$deactivated): FeatureDeactivateResult {
                $deactivated = true;
                return FeatureDeactivateResult::success();
            },
        );

        self::assertFalse($definition->hasOptions());
        self::assertNull($definition->optionsClassName);
        self::assertNull($definition->onUpdateOptions);

        $context = self::context();
        ($definition->onActivate)($context);
        ($definition->onDeactivate)($context);
        self::assertTrue($activated);
        self::assertTrue($deactivated);
    }

    public function test_getOptionsSchema_throws_for_an_optionless_feature(): void
    {
        $definition = FeatureDefinition::createOptionless(
            id: 'my-feature',
            name: 'My Feature',
            onActivate: static fn(): FeatureActivateResult => FeatureActivateResult::success(),
            onDeactivate: static fn(): FeatureDeactivateResult => FeatureDeactivateResult::success(),
        );

        $this->expectException(LogicException::class);
        $definition->getOptionsSchema();
    }
}
