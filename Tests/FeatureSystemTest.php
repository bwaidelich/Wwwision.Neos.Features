<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Neos\Features\FeatureSystem;
use Wwwision\Neos\Features\Model\Feature\BatchActivateResult;
use Wwwision\Neos\Features\Model\Feature\Feature;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDependencyViolation;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\FeatureIds;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\Feature\Features;
use Wwwision\Neos\Features\Model\Feature\FeatureStateConflict;
use Wwwision\Neos\Features\Model\Feature\FeatureUpdateOptionsResult;
use Wwwision\Neos\Features\Model\CommonFeatures\YamlConfigurationFile;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinition;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinitions;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDescription;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureName;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroups;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;
use Wwwision\Neos\Features\Model\FeatureState\FeatureState;
use Wwwision\Neos\Features\Model\FeatureState\FeatureStates;
use Wwwision\Neos\Features\Tests\Fixtures\InMemoryFeatureConfiguration;
use Wwwision\Neos\Features\Tests\Fixtures\InMemoryFeatureStates;
use Wwwision\Neos\Features\Tests\Fixtures\SampleFeatureOptions;
use Wwwision\Neos\Features\Tests\Fixtures\SpyCacheFlusher;
use Wwwision\Types\Exception\CoerceException;

#[CoversClass(FeatureSystem::class)]
#[UsesClass(BatchActivateResult::class)]
#[UsesClass(Feature::class)]
#[UsesClass(FeatureActivateResult::class)]
#[UsesClass(FeatureContext::class)]
#[UsesClass(FeatureDeactivateResult::class)]
#[UsesClass(FeatureDefinition::class)]
#[UsesClass(FeatureDefinitions::class)]
#[UsesClass(FeatureDependencyViolation::class)]
#[UsesClass(FeatureDescription::class)]
#[UsesClass(FeatureGroups::class)]
#[UsesClass(FeatureId::class)]
#[UsesClass(FeatureIds::class)]
#[UsesClass(FeatureName::class)]
#[UsesClass(FeatureState::class)]
#[UsesClass(FeatureStateConflict::class)]
#[UsesClass(FeatureStates::class)]
#[UsesClass(FeatureUpdateOptionsResult::class)]
#[UsesClass(Features::class)]
#[UsesClass(YamlConfigurationFile::class)]
final class FeatureSystemTest extends TestCase
{
    private InMemoryFeatureStates $states;
    private SpyCacheFlusher $cacheFlusher;

    protected function setUp(): void
    {
        $this->states = new InMemoryFeatureStates();
        $this->cacheFlusher = new SpyCacheFlusher();
    }

    /**
     * @param array<FeatureDefinition<FeatureOptions>> $definitions
     */
    private function featureSystem(array $definitions): FeatureSystem
    {
        return new FeatureSystem(
            new InMemoryFeatureConfiguration(FeatureDefinitions::fromArray($definitions)),
            $this->states,
            new FeatureContext(new YamlConfigurationFile('/dev/null'), new YamlConfigurationFile('/dev/null'), []),
            $this->cacheFlusher,
        );
    }

    /**
     * @param callable(FeatureContext, FeatureOptions): FeatureActivateResult|null $onActivate
     * @param callable(FeatureContext, FeatureOptions, FeatureOptions): FeatureUpdateOptionsResult|null $onUpdateOptions
     * @param callable(FeatureContext, FeatureOptions): FeatureDeactivateResult|null $onDeactivate
     * @param list<string> $dependsOn
     * @return FeatureDefinition<FeatureOptions>
     */
    private static function definition(
        string $id,
        string $optionsClassName = SampleFeatureOptions::class,
        ?callable $onActivate = null,
        ?callable $onUpdateOptions = null,
        ?callable $onDeactivate = null,
        array $dependsOn = [],
    ): FeatureDefinition {
        return FeatureDefinition::create(
            id: $id,
            name: ucfirst($id),
            optionsClassName: $optionsClassName,
            onActivate: $onActivate ?? static fn(FeatureContext $c, FeatureOptions $o): FeatureActivateResult => FeatureActivateResult::success(),
            onUpdateOptions: $onUpdateOptions ?? static fn(FeatureContext $c, FeatureOptions $previous, FeatureOptions $new): FeatureUpdateOptionsResult => FeatureUpdateOptionsResult::success(),
            onDeactivate: $onDeactivate ?? static fn(FeatureContext $c, FeatureOptions $o): FeatureDeactivateResult => FeatureDeactivateResult::success(),
            dependsOn: $dependsOn,
        );
    }

    /**
     * @param callable(FeatureContext): FeatureActivateResult|null $onActivate
     * @param callable(FeatureContext): FeatureDeactivateResult|null $onDeactivate
     * @param list<string> $dependsOn
     * @return FeatureDefinition<FeatureOptions>
     */
    private static function optionlessDefinition(
        string $id,
        ?callable $onActivate = null,
        ?callable $onDeactivate = null,
        array $dependsOn = [],
    ): FeatureDefinition {
        return FeatureDefinition::createOptionless(
            id: $id,
            name: ucfirst($id),
            onActivate: $onActivate ?? static fn(FeatureContext $c): FeatureActivateResult => FeatureActivateResult::success(),
            onDeactivate: $onDeactivate ?? static fn(FeatureContext $c): FeatureDeactivateResult => FeatureDeactivateResult::success(),
            dependsOn: $dependsOn,
        );
    }

    private function markActive(string $id): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString($id), true, ['message' => 'active']));
    }

    public function test_getFeatures_returns_one_feature_per_definition(): void
    {
        $system = $this->featureSystem([self::definition('a'), self::definition('b')]);

        $ids = array_map(static fn(Feature $f): string => $f->id->value, iterator_to_array($system->getFeatures()));

        self::assertSame(['a', 'b'], $ids);
    }

    public function test_getFeatures_marks_features_without_state_as_inactive(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        $feature = iterator_to_array($system->getFeatures())[0];

        self::assertFalse($feature->active);
        self::assertNull($feature->options);
    }

    public function test_getFeatures_reflects_stored_state_and_parses_its_options(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'stored', 'threshold' => 3]));
        $system = $this->featureSystem([self::definition('a')]);

        $feature = iterator_to_array($system->getFeatures())[0];

        self::assertTrue($feature->active);
        self::assertInstanceOf(SampleFeatureOptions::class, $feature->options);
        self::assertSame('stored', $feature->options->message);
        self::assertSame(3, $feature->options->threshold);
    }

    public function test_getFeature_returns_the_matching_feature(): void
    {
        $system = $this->featureSystem([self::definition('a'), self::definition('b')]);

        self::assertSame('b', $system->getFeature(FeatureId::fromString('b'))->id->value);
    }

    public function test_getFeature_throws_for_an_unknown_feature(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(InvalidArgumentException::class);
        $system->getFeature(FeatureId::fromString('missing'));
    }

    public function test_activateFeature_invokes_the_activate_callback_with_the_parsed_options(): void
    {
        $activatedWith = null;
        $system = $this->featureSystem([
            self::definition('a', onActivate: function (FeatureContext $context, FeatureOptions $options) use (&$activatedWith): FeatureActivateResult {
                $activatedWith = $options;
                return FeatureActivateResult::success();
            }),
        ]);

        $system->activateFeature(FeatureId::fromString('a'), ['message' => 'go', 'threshold' => 9]);

        self::assertInstanceOf(SampleFeatureOptions::class, $activatedWith);
        self::assertSame('go', $activatedWith->message);
        self::assertSame(9, $activatedWith->threshold);
    }

    public function test_activateFeature_stores_an_active_state_with_normalized_options(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        $system->activateFeature(FeatureId::fromString('a'), ['message' => 'go', 'threshold' => 9]);

        $state = $this->states->loadAll()->get(FeatureId::fromString('a'));
        self::assertNotNull($state);
        self::assertTrue($state->active);
        self::assertSame(['message' => 'go', 'threshold' => 9], $state->options);
    }

    public function test_activateFeature_throws_for_an_unknown_feature(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(InvalidArgumentException::class);
        $system->activateFeature(FeatureId::fromString('missing'), []);
    }

    public function test_activateFeature_rejects_unrecognized_option_keys(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(CoerceException::class);
        $system->activateFeature(FeatureId::fromString('a'), ['message' => 'go', 'bogus' => true]);
    }

    public function test_deactivateFeature_invokes_the_deactivate_callback(): void
    {
        $this->markActive('a');
        $deactivated = false;
        $system = $this->featureSystem([
            self::definition('a', onDeactivate: function (FeatureContext $context, FeatureOptions $options) use (&$deactivated): FeatureDeactivateResult {
                $deactivated = true;
                return FeatureDeactivateResult::success();
            }),
        ]);

        $system->deactivateFeature(FeatureId::fromString('a'));

        self::assertTrue($deactivated);
    }

    public function test_deactivateFeature_passes_the_currently_active_options_to_the_callback(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'stored', 'threshold' => 5]));
        $deactivatedWith = null;
        $system = $this->featureSystem([
            self::definition('a', onDeactivate: function (FeatureContext $context, FeatureOptions $options) use (&$deactivatedWith): FeatureDeactivateResult {
                $deactivatedWith = $options;
                return FeatureDeactivateResult::success();
            }),
        ]);

        $system->deactivateFeature(FeatureId::fromString('a'));

        self::assertInstanceOf(SampleFeatureOptions::class, $deactivatedWith);
        self::assertSame('stored', $deactivatedWith->message);
        self::assertSame(5, $deactivatedWith->threshold);
    }

    public function test_deactivateFeature_keeps_the_state_but_flips_active_to_false(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'stored']));
        $system = $this->featureSystem([self::definition('a')]);

        $system->deactivateFeature(FeatureId::fromString('a'));

        $state = $this->states->loadAll()->get(FeatureId::fromString('a'));
        self::assertNotNull($state);
        self::assertFalse($state->active);
        self::assertSame(['message' => 'stored'], $state->options);
    }

    public function test_deactivateFeature_can_remove_the_state_entirely(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'stored']));
        $system = $this->featureSystem([self::definition('a')]);

        $system->deactivateFeature(FeatureId::fromString('a'), removeState: true);

        self::assertNull($this->states->loadAll()->get(FeatureId::fromString('a')));
    }

    public function test_deactivateFeature_throws_for_an_unknown_feature(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(InvalidArgumentException::class);
        $system->deactivateFeature(FeatureId::fromString('missing'));
    }

    public function test_activateFeature_is_blocked_while_a_dependency_is_inactive(): void
    {
        $system = $this->featureSystem([self::definition('a'), self::definition('b', dependsOn: ['a'])]);

        $this->expectException(FeatureDependencyViolation::class);
        $system->activateFeature(FeatureId::fromString('b'), []);
    }

    public function test_activateFeature_does_not_store_a_state_when_blocked_by_a_dependency(): void
    {
        $system = $this->featureSystem([self::definition('a'), self::definition('b', dependsOn: ['a'])]);

        try {
            $system->activateFeature(FeatureId::fromString('b'), []);
        } catch (FeatureDependencyViolation) {
        }

        self::assertNull($this->states->loadAll()->get(FeatureId::fromString('b')));
    }

    public function test_activateFeature_succeeds_once_all_dependencies_are_active(): void
    {
        $this->markActive('a');
        $system = $this->featureSystem([self::definition('a'), self::optionlessDefinition('b', dependsOn: ['a'])]);

        $system->activateFeature(FeatureId::fromString('b'), []);

        self::assertTrue($this->states->loadAll()->get(FeatureId::fromString('b'))?->active);
    }

    public function test_deactivateFeature_is_blocked_while_an_active_dependent_requires_it(): void
    {
        $this->markActive('a');
        $this->markActive('b');
        $system = $this->featureSystem([self::definition('a'), self::definition('b', dependsOn: ['a'])]);

        $this->expectException(FeatureDependencyViolation::class);
        $system->deactivateFeature(FeatureId::fromString('a'));
    }

    public function test_deactivateFeature_succeeds_when_its_dependents_are_inactive(): void
    {
        $this->markActive('a');
        $system = $this->featureSystem([self::definition('a'), self::definition('b', dependsOn: ['a'])]);

        $system->deactivateFeature(FeatureId::fromString('a'), removeState: true);

        self::assertNull($this->states->loadAll()->get(FeatureId::fromString('a')));
    }

    public function test_activateFeature_is_blocked_when_the_feature_is_already_active(): void
    {
        $this->markActive('a');
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(FeatureStateConflict::class);
        $system->activateFeature(FeatureId::fromString('a'), []);
    }

    public function test_activateFeature_does_not_invoke_the_activate_callback_when_already_active(): void
    {
        $this->markActive('a');
        $activated = false;
        $system = $this->featureSystem([
            self::definition('a', onActivate: function (FeatureContext $context, FeatureOptions $options) use (&$activated): FeatureActivateResult {
                $activated = true;
                return FeatureActivateResult::success();
            }),
        ]);

        try {
            $system->activateFeature(FeatureId::fromString('a'), []);
        } catch (FeatureStateConflict) {
        }

        self::assertFalse($activated);
    }

    public function test_activateFeature_does_not_overwrite_the_stored_options_when_already_active(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'stored', 'threshold' => 3]));
        $system = $this->featureSystem([self::definition('a')]);

        try {
            $system->activateFeature(FeatureId::fromString('a'), ['message' => 'new', 'threshold' => 9]);
        } catch (FeatureStateConflict) {
        }

        $state = $this->states->loadAll()->get(FeatureId::fromString('a'));
        self::assertSame(['message' => 'stored', 'threshold' => 3], $state?->options);
    }

    public function test_deactivateFeature_is_blocked_when_the_feature_has_no_state(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(FeatureStateConflict::class);
        $system->deactivateFeature(FeatureId::fromString('a'));
    }

    public function test_deactivateFeature_is_blocked_when_the_feature_is_already_inactive(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), false, ['message' => 'stored']));
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(FeatureStateConflict::class);
        $system->deactivateFeature(FeatureId::fromString('a'));
    }

    public function test_deactivateFeature_does_not_invoke_the_deactivate_callback_when_already_inactive(): void
    {
        $deactivated = false;
        $system = $this->featureSystem([
            self::definition('a', onDeactivate: function () use (&$deactivated): FeatureDeactivateResult {
                $deactivated = true;
                return FeatureDeactivateResult::success();
            }),
        ]);

        try {
            $system->deactivateFeature(FeatureId::fromString('a'));
        } catch (FeatureStateConflict) {
        }

        self::assertFalse($deactivated);
    }

    public function test_updateFeatureOptions_invokes_the_callback_with_the_previous_and_new_options(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'old', 'threshold' => 1]));
        $previous = null;
        $new = null;
        $system = $this->featureSystem([
            self::definition('a', onUpdateOptions: function (FeatureContext $context, FeatureOptions $p, FeatureOptions $n) use (&$previous, &$new): FeatureUpdateOptionsResult {
                $previous = $p;
                $new = $n;
                return FeatureUpdateOptionsResult::success();
            }),
        ]);

        $system->updateFeatureOptions(FeatureId::fromString('a'), ['message' => 'new', 'threshold' => 2]);

        self::assertInstanceOf(SampleFeatureOptions::class, $previous);
        self::assertSame('old', $previous->message);
        self::assertSame(1, $previous->threshold);
        self::assertInstanceOf(SampleFeatureOptions::class, $new);
        self::assertSame('new', $new->message);
        self::assertSame(2, $new->threshold);
    }

    public function test_updateFeatureOptions_stores_the_new_normalized_options_and_keeps_the_feature_active(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'old', 'threshold' => 1]));
        $system = $this->featureSystem([self::definition('a')]);

        $system->updateFeatureOptions(FeatureId::fromString('a'), ['message' => 'new', 'threshold' => 2]);

        $state = $this->states->loadAll()->get(FeatureId::fromString('a'));
        self::assertNotNull($state);
        self::assertTrue($state->active);
        self::assertSame(['message' => 'new', 'threshold' => 2], $state->options);
    }

    public function test_updateFeatureOptions_throws_for_an_unknown_feature(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(InvalidArgumentException::class);
        $system->updateFeatureOptions(FeatureId::fromString('missing'), []);
    }

    public function test_updateFeatureOptions_is_blocked_when_the_feature_has_no_state(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(FeatureStateConflict::class);
        $system->updateFeatureOptions(FeatureId::fromString('a'), ['message' => 'new']);
    }

    public function test_updateFeatureOptions_is_blocked_when_the_feature_is_inactive(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), false, ['message' => 'old']));
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(FeatureStateConflict::class);
        $system->updateFeatureOptions(FeatureId::fromString('a'), ['message' => 'new']);
    }

    public function test_updateFeatureOptions_does_not_invoke_the_callback_when_inactive(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), false, ['message' => 'old']));
        $updated = false;
        $system = $this->featureSystem([
            self::definition('a', onUpdateOptions: function (FeatureContext $context, FeatureOptions $p, FeatureOptions $n) use (&$updated): FeatureUpdateOptionsResult {
                $updated = true;
                return FeatureUpdateOptionsResult::success();
            }),
        ]);

        try {
            $system->updateFeatureOptions(FeatureId::fromString('a'), ['message' => 'new']);
        } catch (FeatureStateConflict) {
        }

        self::assertFalse($updated);
    }

    public function test_updateFeatureOptions_does_not_change_the_stored_options_when_inactive(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), false, ['message' => 'old', 'threshold' => 1]));
        $system = $this->featureSystem([self::definition('a')]);

        try {
            $system->updateFeatureOptions(FeatureId::fromString('a'), ['message' => 'new', 'threshold' => 2]);
        } catch (FeatureStateConflict) {
        }

        $state = $this->states->loadAll()->get(FeatureId::fromString('a'));
        self::assertSame(['message' => 'old', 'threshold' => 1], $state?->options);
    }

    public function test_updateFeatureOptions_rejects_unrecognized_option_keys(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'old']));
        $system = $this->featureSystem([self::definition('a')]);

        $this->expectException(CoerceException::class);
        $system->updateFeatureOptions(FeatureId::fromString('a'), ['message' => 'new', 'bogus' => true]);
    }

    public function test_getFeature_exposes_unmet_dependencies_and_active_dependents(): void
    {
        $this->markActive('b');
        $system = $this->featureSystem([self::definition('a'), self::optionlessDefinition('b', dependsOn: ['a'])]);

        $a = $system->getFeature(FeatureId::fromString('a'));
        $b = $system->getFeature(FeatureId::fromString('b'));

        self::assertSame(['a'], $b->unmetDependencies->toStringArray());
        self::assertSame(['b'], $a->activeDependents->toStringArray());
        self::assertFalse($b->isActivatable());
        self::assertFalse($a->isDeactivatable());
    }

    public function test_activateFeature_of_an_optionless_feature_invokes_the_callback_and_stores_empty_options(): void
    {
        $activated = false;
        $system = $this->featureSystem([
            self::optionlessDefinition('a', onActivate: function () use (&$activated): FeatureActivateResult {
                $activated = true;
                return FeatureActivateResult::success();
            }),
        ]);

        $system->activateFeature(FeatureId::fromString('a'), []);

        self::assertTrue($activated);
        $state = $this->states->loadAll()->get(FeatureId::fromString('a'));
        self::assertNotNull($state);
        self::assertTrue($state->active);
        self::assertSame([], $state->options);
    }

    public function test_activateFeature_of_an_optionless_feature_rejects_non_empty_options(): void
    {
        $system = $this->featureSystem([self::optionlessDefinition('a')]);

        $this->expectException(InvalidArgumentException::class);
        $system->activateFeature(FeatureId::fromString('a'), ['foo' => 'bar']);
    }

    public function test_deactivateFeature_of_an_optionless_feature_invokes_the_callback(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), true, []));
        $deactivated = false;
        $system = $this->featureSystem([
            self::optionlessDefinition('a', onDeactivate: function () use (&$deactivated): FeatureDeactivateResult {
                $deactivated = true;
                return FeatureDeactivateResult::success();
            }),
        ]);

        $system->deactivateFeature(FeatureId::fromString('a'));

        self::assertTrue($deactivated);
    }

    public function test_updateFeatureOptions_is_blocked_for_an_optionless_feature(): void
    {
        $this->states->store(new FeatureState(FeatureId::fromString('a'), true, []));
        $system = $this->featureSystem([self::optionlessDefinition('a')]);

        $this->expectException(FeatureStateConflict::class);
        $system->updateFeatureOptions(FeatureId::fromString('a'), []);
    }

    // ------------------------ cache flushing ------------------------

    public function test_activateFeature_flushes_caches_once(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        $system->activateFeature(FeatureId::fromString('a'), ['message' => 'go']);

        self::assertSame(1, $this->cacheFlusher->flushCount);
    }

    public function test_activateFeature_does_not_flush_caches_when_blocked_by_a_dependency(): void
    {
        $system = $this->featureSystem([self::definition('a'), self::optionlessDefinition('b', dependsOn: ['a'])]);

        try {
            $system->activateFeature(FeatureId::fromString('b'), []);
        } catch (FeatureDependencyViolation) {
        }

        self::assertSame(0, $this->cacheFlusher->flushCount);
    }

    public function test_deactivateFeature_flushes_caches_once(): void
    {
        $this->markActive('a');
        $system = $this->featureSystem([self::definition('a')]);

        $system->deactivateFeature(FeatureId::fromString('a'), removeState: true);

        self::assertSame(1, $this->cacheFlusher->flushCount);
    }

    public function test_deactivateFeature_does_not_flush_caches_when_already_inactive(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        try {
            $system->deactivateFeature(FeatureId::fromString('a'));
        } catch (FeatureStateConflict) {
        }

        self::assertSame(0, $this->cacheFlusher->flushCount);
    }

    public function test_updateFeatureOptions_flushes_caches_once(): void
    {
        $this->markActive('a');
        $system = $this->featureSystem([self::definition('a')]);

        $system->updateFeatureOptions(FeatureId::fromString('a'), ['message' => 'new']);

        self::assertSame(1, $this->cacheFlusher->flushCount);
    }

    public function test_updateFeatureOptions_does_not_flush_caches_when_inactive(): void
    {
        $system = $this->featureSystem([self::definition('a')]);

        try {
            $system->updateFeatureOptions(FeatureId::fromString('a'), ['message' => 'new']);
        } catch (FeatureStateConflict) {
        }

        self::assertSame(0, $this->cacheFlusher->flushCount);
    }

    // ------------------------ batch activation ------------------------

    /**
     * @param list<string> $featureIds
     */
    private static function featureIds(array $featureIds): FeatureIds
    {
        return FeatureIds::fromArray($featureIds);
    }

    public function test_activateFeatures_activates_all_requested_features(): void
    {
        $system = $this->featureSystem([self::optionlessDefinition('a'), self::optionlessDefinition('b')]);

        $result = $system->activateFeatures(self::featureIds(['a', 'b']));

        self::assertSame(['a', 'b'], $result->activated->toStringArray());
        self::assertFalse($result->hasFailure());
        self::assertTrue($this->states->loadAll()->get(FeatureId::fromString('a'))?->active);
        self::assertTrue($this->states->loadAll()->get(FeatureId::fromString('b'))?->active);
    }

    public function test_activateFeatures_activates_dependencies_before_their_dependents(): void
    {
        $activationOrder = [];
        $recordingActivate = function (string $id) use (&$activationOrder): callable {
            return static function () use ($id, &$activationOrder): FeatureActivateResult {
                $activationOrder[] = $id;
                return FeatureActivateResult::success();
            };
        };
        $system = $this->featureSystem([
            self::optionlessDefinition('c', onActivate: $recordingActivate('c'), dependsOn: ['b']),
            self::optionlessDefinition('a', onActivate: $recordingActivate('a')),
            self::optionlessDefinition('b', onActivate: $recordingActivate('b'), dependsOn: ['a']),
        ]);

        $result = $system->activateFeatures(self::featureIds(['c', 'a']));

        self::assertSame(['a', 'b', 'c'], $activationOrder);
        self::assertFalse($result->hasFailure());
    }

    public function test_activateFeatures_includes_inactive_dependencies_that_were_not_requested(): void
    {
        $system = $this->featureSystem([self::optionlessDefinition('a'), self::optionlessDefinition('b', dependsOn: ['a'])]);

        $result = $system->activateFeatures(self::featureIds(['b']));

        self::assertSame(['a', 'b'], $result->activated->toStringArray());
    }

    public function test_activateFeatures_skips_features_that_are_already_active(): void
    {
        $this->markActive('a');
        $system = $this->featureSystem([self::definition('a'), self::optionlessDefinition('b')]);

        $result = $system->activateFeatures(self::featureIds(['a', 'b']));

        self::assertSame(['b'], $result->activated->toStringArray());
        self::assertSame(['a'], $result->skipped->toStringArray());
        self::assertFalse($result->hasFailure());
    }

    public function test_activateFeatures_does_not_activate_dependencies_that_are_already_active(): void
    {
        $this->markActive('a');
        $activated = false;
        $system = $this->featureSystem([
            self::definition('a', onActivate: function () use (&$activated): FeatureActivateResult {
                $activated = true;
                return FeatureActivateResult::success();
            }),
            self::optionlessDefinition('b', dependsOn: ['a']),
        ]);

        $result = $system->activateFeatures(self::featureIds(['b']));

        self::assertFalse($activated);
        self::assertSame(['b'], $result->activated->toStringArray());
    }

    public function test_activateFeatures_passes_the_options_to_the_corresponding_feature(): void
    {
        $activatedWith = null;
        $system = $this->featureSystem([
            self::definition('a', onActivate: function (FeatureContext $context, FeatureOptions $options) use (&$activatedWith): FeatureActivateResult {
                $activatedWith = $options;
                return FeatureActivateResult::success();
            }),
            self::optionlessDefinition('b'),
        ]);

        $system->activateFeatures(self::featureIds(['a', 'b']), ['a' => ['message' => 'go', 'threshold' => 9]]);

        self::assertInstanceOf(SampleFeatureOptions::class, $activatedWith);
        self::assertSame('go', $activatedWith->message);
        self::assertSame(9, $activatedWith->threshold);
    }

    public function test_activateFeatures_stops_at_the_first_failure_and_keeps_previous_activations(): void
    {
        $system = $this->featureSystem([
            self::optionlessDefinition('a'),
            self::optionlessDefinition('b', onActivate: static fn(): FeatureActivateResult => throw new \RuntimeException('activation failed')),
            self::optionlessDefinition('c'),
        ]);

        $result = $system->activateFeatures(self::featureIds(['a', 'b', 'c']));

        self::assertSame(['a'], $result->activated->toStringArray());
        self::assertTrue($result->hasFailure());
        self::assertSame('b', $result->failedFeatureId?->value);
        self::assertSame('activation failed', $result->failureMessage);
        self::assertTrue($this->states->loadAll()->get(FeatureId::fromString('a'))?->active);
        self::assertNull($this->states->loadAll()->get(FeatureId::fromString('b')));
        self::assertNull($this->states->loadAll()->get(FeatureId::fromString('c')));
    }

    public function test_activateFeatures_flushes_caches_only_once(): void
    {
        $system = $this->featureSystem([self::optionlessDefinition('a'), self::optionlessDefinition('b'), self::optionlessDefinition('c')]);

        $system->activateFeatures(self::featureIds(['a', 'b', 'c']));

        self::assertSame(1, $this->cacheFlusher->flushCount);
    }

    public function test_activateFeatures_flushes_caches_once_even_when_a_later_feature_fails(): void
    {
        $system = $this->featureSystem([
            self::optionlessDefinition('a'),
            self::optionlessDefinition('b', onActivate: static fn(): FeatureActivateResult => throw new \RuntimeException('activation failed')),
        ]);

        $system->activateFeatures(self::featureIds(['a', 'b']));

        self::assertSame(1, $this->cacheFlusher->flushCount);
    }

    public function test_activateFeatures_does_not_flush_caches_when_no_feature_was_activated(): void
    {
        $this->markActive('a');
        $system = $this->featureSystem([self::definition('a')]);

        $result = $system->activateFeatures(self::featureIds(['a']));

        self::assertSame([], $result->activated->toStringArray());
        self::assertSame(0, $this->cacheFlusher->flushCount);
    }

    public function test_activateFeatures_throws_for_an_unknown_feature(): void
    {
        $system = $this->featureSystem([self::optionlessDefinition('a')]);

        $this->expectException(InvalidArgumentException::class);
        $system->activateFeatures(self::featureIds(['a', 'missing']));
    }

    public function test_activateFeatures_throws_for_cyclic_dependencies(): void
    {
        $system = $this->featureSystem([
            self::optionlessDefinition('a', dependsOn: ['b']),
            self::optionlessDefinition('b', dependsOn: ['a']),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cyclic feature dependency/');
        $system->activateFeatures(self::featureIds(['a']));
    }

    public function test_getFeaturesForActivation_returns_the_expanded_selection_in_activation_order(): void
    {
        $system = $this->featureSystem([
            self::optionlessDefinition('a'),
            self::optionlessDefinition('b', dependsOn: ['a']),
            self::optionlessDefinition('c', dependsOn: ['b']),
        ]);

        $features = $system->getFeaturesForActivation(FeatureIds::fromArray(['c']));

        $ids = array_map(static fn(Feature $f): string => $f->id->value, iterator_to_array($features));
        self::assertSame(['a', 'b', 'c'], $ids);
    }

    public function test_getFeaturesForActivation_excludes_features_that_are_already_active(): void
    {
        $this->markActive('a');
        $system = $this->featureSystem([
            self::definition('a'),
            self::optionlessDefinition('b', dependsOn: ['a']),
        ]);

        $features = $system->getFeaturesForActivation(FeatureIds::fromArray(['a', 'b']));

        $ids = array_map(static fn(Feature $f): string => $f->id->value, iterator_to_array($features));
        self::assertSame(['b'], $ids);
    }
}
