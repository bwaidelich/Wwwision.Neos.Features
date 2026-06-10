<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Adapter;

use InvalidArgumentException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Wwwision\Neos\Features\Adapter\FeatureProviderFromSettings;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroupId;
use Wwwision\Neos\Features\Tests\Fixtures\SampleFeatureFactory;

#[CoversClass(FeatureProviderFromSettings::class)]
final class FeatureProviderFromSettingsTest extends TestCase
{
    /**
     * @param array<mixed> $features
     * @param array<mixed> $groups
     */
    private function provider(array $features, array $groups = []): FeatureProviderFromSettings
    {
        // none of the fixtures below declare an "objectName", so the ObjectManager is never consulted
        return new FeatureProviderFromSettings($features, $groups, $this->createStub(ObjectManagerInterface::class));
    }

    /**
     * @param array<mixed> $features
     */
    private function providerWithObjectManagerReturning(array $features, mixed $resolvedObject): FeatureProviderFromSettings
    {
        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturn($resolvedObject);
        return new FeatureProviderFromSettings($features, [], $objectManager);
    }

    public function test_builds_groups_with_their_presentation_data(): void
    {
        $provider = $this->provider([], [
            'content' => ['name' => 'Content', 'description' => 'Content features', 'icon' => 'file'],
        ]);

        $group = $provider->getFeatureGroups()->get(FeatureGroupId::fromString('content'));

        self::assertNotNull($group);
        self::assertSame('Content', (string) $group->name);
        self::assertSame('Content features', (string) $group->description);
        self::assertSame('file', (string) $group->icon);
    }

    public function test_builds_definitions_with_dependsOn_and_group(): void
    {
        $provider = $this->provider(
            [
                'a' => [],
                'b' => ['dependsOn' => ['a'], 'group' => 'content'],
            ],
            ['content' => ['name' => 'Content']],
        );

        $definition = $provider->getFeatureDefinitions()->get(FeatureId::fromString('b'));

        self::assertNotNull($definition);
        self::assertSame(['a'], $definition->dependsOn->toStringArray());
        self::assertSame('content', $definition->group?->value);
    }

    public function test_throws_when_a_feature_depends_on_an_unknown_feature(): void
    {
        $provider = $this->provider(['a' => ['dependsOn' => ['missing']]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/depends on unknown feature "missing"/');
        $provider->getFeatureDefinitions();
    }

    public function test_throws_on_a_dependency_cycle(): void
    {
        $provider = $this->provider([
            'a' => ['dependsOn' => ['b']],
            'b' => ['dependsOn' => ['a']],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cycle/');
        $provider->getFeatureDefinitions();
    }

    public function test_throws_when_a_feature_references_an_unknown_group(): void
    {
        $provider = $this->provider(['a' => ['group' => 'missing']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/unknown group "missing"/');
        $provider->getFeatureDefinitions();
    }

    public function test_accepts_a_valid_graph(): void
    {
        $provider = $this->provider(
            [
                'a' => [],
                'b' => ['dependsOn' => ['a']],
                'c' => ['dependsOn' => ['a', 'b'], 'group' => 'content'],
            ],
            ['content' => ['name' => 'Content']],
        );

        $ids = $provider->getFeatureDefinitions()->map(static fn($d): string => $d->id->value);

        self::assertSame(['a', 'b', 'c'], $ids);
    }

    public function test_builds_a_definition_from_a_factory_and_passes_the_parsed_factory_options(): void
    {
        $factory = new SampleFeatureFactory();
        $provider = $this->providerWithObjectManagerReturning(
            ['a' => ['factoryClassName' => SampleFeatureFactory::class, 'options' => ['label' => 'hello']]],
            $factory,
        );

        $definition = $provider->getFeatureDefinitions()->get(FeatureId::fromString('a'));

        self::assertNotNull($definition);
        self::assertSame(['label' => 'hello'], $factory->receivedOptions);
    }

    public function test_throws_when_both_objectName_and_factoryClassName_are_set(): void
    {
        $provider = $this->provider([
            'a' => ['objectName' => 'Some\Object', 'factoryClassName' => SampleFeatureFactory::class],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must not declare both "objectName" and "factoryClassName"/');
        $provider->getFeatureDefinitions();
    }

    public function test_throws_when_options_are_set_without_a_factoryClassName(): void
    {
        $provider = $this->provider([
            'a' => ['objectName' => 'Some\Object', 'options' => ['label' => 'hello']],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/declares "options" but no "factoryClassName"/');
        $provider->getFeatureDefinitions();
    }

    public function test_throws_when_the_factory_class_does_not_implement_the_factory_interface(): void
    {
        $provider = $this->providerWithObjectManagerReturning(
            ['a' => ['factoryClassName' => stdClass::class]],
            new stdClass(),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must implement/');
        $provider->getFeatureDefinitions();
    }
}
