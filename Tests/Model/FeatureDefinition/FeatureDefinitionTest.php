<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\FeatureDefinition;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Neos\Features\Model\Feature\EmptyFeatureOptions;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinition;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDescription;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureIcon;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureName;
use Wwwision\Neos\Features\Tests\Fixtures\SampleFeatureOptions;

#[CoversClass(FeatureDefinition::class)]
final class FeatureDefinitionTest extends TestCase
{
    /**
     * @return callable(FeatureOptions): FeatureActivateResult
     */
    private static function noopActivate(): callable
    {
        return static fn(FeatureOptions $options): FeatureActivateResult => FeatureActivateResult::success();
    }

    /**
     * @return callable(): FeatureDeactivateResult
     */
    private static function noopDeactivate(): callable
    {
        return static fn(): FeatureDeactivateResult => FeatureDeactivateResult::success();
    }

    public function test_create_coerces_string_arguments_to_value_objects(): void
    {
        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: EmptyFeatureOptions::class,
            onActivate: self::noopActivate(),
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
            optionsClassName: EmptyFeatureOptions::class,
            onActivate: self::noopActivate(),
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
            optionsClassName: EmptyFeatureOptions::class,
            onActivate: self::noopActivate(),
            onDeactivate: self::noopDeactivate(),
        );

        self::assertSame('', (string) $definition->description);
    }

    public function test_create_defaults_icon_to_null(): void
    {
        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: EmptyFeatureOptions::class,
            onActivate: self::noopActivate(),
            onDeactivate: self::noopDeactivate(),
        );

        self::assertNull($definition->icon);
    }

    public function test_the_activate_and_deactivate_callbacks_are_wired_up(): void
    {
        $activatedWith = null;
        $deactivated = false;

        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: SampleFeatureOptions::class,
            onActivate: function (FeatureOptions $options) use (&$activatedWith): FeatureActivateResult {
                $activatedWith = $options;
                return FeatureActivateResult::success();
            },
            onDeactivate: function () use (&$deactivated): FeatureDeactivateResult {
                $deactivated = true;
                return FeatureDeactivateResult::success();
            },
        );

        $options = new SampleFeatureOptions('hi');
        ($definition->onActivate)($options);
        ($definition->onDeactivate)();

        self::assertSame($options, $activatedWith);
        self::assertTrue($deactivated);
    }

    public function test_parseOptions_instantiates_the_options_class(): void
    {
        $definition = FeatureDefinition::create(
            id: 'my-feature',
            name: 'My Feature',
            optionsClassName: SampleFeatureOptions::class,
            onActivate: self::noopActivate(),
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
            onDeactivate: self::noopDeactivate(),
        );

        $options = $definition->parseOptions(['message' => 'hello', 'unknown' => 'ignored']);

        self::assertInstanceOf(SampleFeatureOptions::class, $options);
        self::assertSame('hello', $options->message);
    }
}
