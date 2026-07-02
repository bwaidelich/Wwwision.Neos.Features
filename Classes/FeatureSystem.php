<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\Feature\BatchActivateResult;
use Wwwision\Neos\Features\Model\Feature\Feature;
use Wwwision\Neos\Features\Model\Feature\FeatureDependencyViolation;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\FeatureIds;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\Feature\Features;
use Wwwision\Neos\Features\Model\Feature\FeatureStateConflict;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinition;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroups;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureContext;
use Wwwision\Neos\Features\Model\FeatureState\FeatureState;
use Wwwision\Neos\Features\Model\FeatureState\FeatureStates;
use Wwwision\Neos\Features\Ports\ForFlushingCaches;
use Wwwision\Neos\Features\Ports\ForProvidingFeatureConfiguration;
use Wwwision\Neos\Features\Ports\ForStoringFeatureStates;
use Wwwision\Types\Normalizer\Normalizer;
use Wwwision\Types\Options;

final class FeatureSystem
{
    private FeatureStates|null $featureStatesRuntimeCache = null;

    public function __construct(
        private readonly ForProvidingFeatureConfiguration $forProvidingFeatureConfiguration,
        private readonly ForStoringFeatureStates $forStoringFeatureStates,
        private readonly FeatureContext $featureContext,
        private readonly ForFlushingCaches $forFlushingCaches,
    ) {}

    public function getFeatureGroups(): FeatureGroups
    {
        return $this->forProvidingFeatureConfiguration->getFeatureGroups();
    }

    public function getFeatures(): Features
    {
        return Features::fromArray(
            $this->forProvidingFeatureConfiguration->getFeatureDefinitions()->map($this->buildFeatureFromDefinition(...)),
        );
    }

    /**
     * @return Feature<FeatureOptions>
     */
    public function getFeature(FeatureId $featureId): Feature
    {
        return $this->buildFeatureFromDefinition($this->requireDefinition($featureId, 1779026887));
    }

    /**
     * @param array<mixed> $options
     */
    public function activateFeature(FeatureId $featureId, array $options): void
    {
        $this->performActivation($featureId, $options);
        $this->forFlushingCaches->flushCaches();
    }

    /**
     * The features that {@see activateFeatures()} would activate for the given selection: the inactive features
     * of the selection plus, transitively, their inactive dependencies – ordered such that dependencies come
     * before the features that require them.
     */
    public function getFeaturesForActivation(FeatureIds $featureIds): Features
    {
        return Features::fromArray(array_map($this->buildFeatureFromDefinition(...), $this->expandWithInactiveDependencies($featureIds)));
    }

    /**
     * Activates the given features and, transitively, their inactive dependencies in one batch (dependencies first).
     *
     * Features that are already active are skipped silently. Processing stops at the first feature that fails to
     * activate; features activated up to that point remain active. Caches are flushed once at the end of the
     * batch, and only if at least one feature was actually activated.
     *
     * @param array<string, array<mixed>> $optionsByFeatureId options for the configurable features of the batch, indexed by feature id
     */
    public function activateFeatures(FeatureIds $featureIds, array $optionsByFeatureId = []): BatchActivateResult
    {
        $skipped = [];
        foreach ($featureIds as $featureId) {
            if ($this->getFeatureState($featureId)?->active === true) {
                $skipped[] = $featureId;
            }
        }
        $activated = [];
        $failedFeatureId = null;
        $failureMessage = null;
        foreach ($this->expandWithInactiveDependencies($featureIds) as $featureDefinition) {
            try {
                $this->performActivation($featureDefinition->id, $optionsByFeatureId[$featureDefinition->id->value] ?? []);
            } catch (Throwable $exception) {
                $failedFeatureId = $featureDefinition->id;
                $failureMessage = $exception->getMessage();
                break;
            }
            $activated[] = $featureDefinition->id;
        }
        if ($activated !== []) {
            $this->forFlushingCaches->flushCaches();
        }
        if ($failedFeatureId !== null && $failureMessage !== null) {
            return BatchActivateResult::failure(FeatureIds::fromArray($activated), FeatureIds::fromArray($skipped), $failedFeatureId, $failureMessage);
        }
        return BatchActivateResult::success(FeatureIds::fromArray($activated), FeatureIds::fromArray($skipped));
    }

    /**
     * @param array<mixed> $options
     */
    private function performActivation(FeatureId $featureId, array $options): void
    {
        $featureDefinition = $this->requireDefinition($featureId, 1779121538);
        if ($this->getFeatureState($featureId)?->active === true) {
            throw FeatureStateConflict::cannotActivateBecauseAlreadyActive($featureId);
        }
        $inactiveDependencies = $this->unmetDependencies($featureDefinition->dependsOn);
        if (!$inactiveDependencies->isEmpty()) {
            throw FeatureDependencyViolation::cannotActivateBecauseDependenciesInactive($featureId, $inactiveDependencies);
        }
        if (!$featureDefinition->hasOptions()) {
            Assert::isEmpty($options, sprintf('Feature "%s" takes no options', $featureId->value));
            // TODO: Evaluate result
            ($featureDefinition->onActivate)($this->featureContext);
            $this->storeAndInvalidate(new FeatureState($featureId, true, []));
            return;
        }
        $featureOptions = $this->instantiateOptions($featureDefinition, $options);
        // TODO: Evaluate result
        ($featureDefinition->onActivate)($this->featureContext, $featureOptions);

        $this->storeAndInvalidate(new FeatureState($featureId, true, $this->normalizeOptions($featureOptions)));
    }

    /**
     * Resolves the definitions of the given features (unless they are already active) plus, transitively, those of
     * their inactive dependencies, in activation order: every feature comes after all of its dependencies.
     *
     * @return list<FeatureDefinition<FeatureOptions>>
     */
    private function expandWithInactiveDependencies(FeatureIds $featureIds): array
    {
        $definitions = [];
        $visited = [];
        $visiting = [];
        $visit = function (FeatureId $featureId) use (&$visit, &$definitions, &$visited, &$visiting): void {
            if (isset($visited[$featureId->value])) {
                return;
            }
            if (isset($visiting[$featureId->value])) {
                throw new RuntimeException(sprintf('Cyclic feature dependency detected involving feature "%s"', $featureId->value), 1782032010);
            }
            if ($this->getFeatureState($featureId)?->active === true) {
                $visited[$featureId->value] = true;
                return;
            }
            $visiting[$featureId->value] = true;
            $featureDefinition = $this->requireDefinition($featureId, 1782032011);
            foreach ($featureDefinition->dependsOn as $dependencyId) {
                $visit($dependencyId);
            }
            unset($visiting[$featureId->value]);
            $visited[$featureId->value] = true;
            $definitions[] = $featureDefinition;
        };
        foreach ($featureIds as $featureId) {
            $visit($featureId);
        }
        return $definitions;
    }

    /**
     * @param array<mixed> $newOptions
     */
    public function updateFeatureOptions(FeatureId $featureId, array $newOptions): void
    {
        $featureDefinition = $this->requireDefinition($featureId, 1780681405);
        $onUpdateOptions = $featureDefinition->onUpdateOptions;
        if ($onUpdateOptions === null) {
            // optionless features have no options to update
            throw FeatureStateConflict::cannotUpdateOptionsBecauseFeatureHasNoOptions($featureId);
        }

        $currentState = $this->getFeatureState($featureId);
        if ($currentState === null || !$currentState->active) {
            throw FeatureStateConflict::cannotUpdateOptionsBecauseInactive($featureId);
        }
        $previousFeatureOptions = $featureDefinition->parseOptions($currentState->options);
        $newFeatureOptions = $this->instantiateOptions($featureDefinition, $newOptions);

        // TODO: Evaluate result
        $onUpdateOptions($this->featureContext, $previousFeatureOptions, $newFeatureOptions);

        $this->storeAndInvalidate(new FeatureState($featureId, true, $this->normalizeOptions($newFeatureOptions)));
        $this->forFlushingCaches->flushCaches();
    }

    public function deactivateFeature(FeatureId $featureId, bool $removeState = false): void
    {
        $featureDefinition = $this->requireDefinition($featureId, 1779122634);
        $currentState = $this->getFeatureState($featureId);
        if ($currentState === null || !$currentState->active) {
            throw FeatureStateConflict::cannotDeactivateBecauseAlreadyInactive($featureId);
        }
        $activeDependents = $this->activeDependents($featureId);
        if (!$activeDependents->isEmpty()) {
            throw FeatureDependencyViolation::cannotDeactivateBecauseRequiredByActiveDependents($featureId, $activeDependents);
        }
        // TODO: Evaluate result
        if ($featureDefinition->hasOptions()) {
            ($featureDefinition->onDeactivate)($this->featureContext, $featureDefinition->parseOptions($currentState->options));
        } else {
            ($featureDefinition->onDeactivate)($this->featureContext);
        }
        if ($removeState) {
            $this->forStoringFeatureStates->remove($featureId);
            $this->featureStatesRuntimeCache = null;
        } else {
            $this->storeAndInvalidate($currentState->with(active: false));
        }
        $this->forFlushingCaches->flushCaches();
    }

    /**
     * @return FeatureDefinition<FeatureOptions>
     */
    private function requireDefinition(FeatureId $featureId, int $exceptionCode): FeatureDefinition
    {
        $featureDefinition = $this->forProvidingFeatureConfiguration->getFeatureDefinitions()->get($featureId);
        if ($featureDefinition === null) {
            throw new InvalidArgumentException(sprintf('Feature with id "%s" does not exist', $featureId), $exceptionCode);
        }
        return $featureDefinition;
    }

    /**
     * Instantiates and validates user-supplied options against the feature's options schema, rejecting unrecognized keys.
     *
     * @param FeatureDefinition<FeatureOptions> $featureDefinition
     * @param array<mixed> $options
     */
    private function instantiateOptions(FeatureDefinition $featureDefinition, array $options): FeatureOptions
    {
        $featureOptions = $featureDefinition->getOptionsSchema()->instantiate($options, Options::create(ignoreUnrecognizedKeys: false));
        Assert::isInstanceOf($featureOptions, FeatureOptions::class);
        return $featureOptions;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeOptions(FeatureOptions $featureOptions): array
    {
        $normalizedOptions = new Normalizer()->normalize($featureOptions);
        Assert::isMap($normalizedOptions);
        return $normalizedOptions;
    }

    private function storeAndInvalidate(FeatureState $featureState): void
    {
        $this->forStoringFeatureStates->store($featureState);
        $this->featureStatesRuntimeCache = null;
    }

    /**
     * The subset of the given dependencies that is currently *not* active.
     */
    private function unmetDependencies(FeatureIds $dependsOn): FeatureIds
    {
        $unmet = [];
        foreach ($dependsOn as $dependencyId) {
            $state = $this->getFeatureState($dependencyId);
            if ($state === null || !$state->active) {
                $unmet[] = $dependencyId;
            }
        }
        return FeatureIds::fromArray($unmet);
    }

    /**
     * The currently active features that depend on the given feature.
     */
    private function activeDependents(FeatureId $featureId): FeatureIds
    {
        $dependents = [];
        foreach ($this->forProvidingFeatureConfiguration->getFeatureDefinitions() as $definition) {
            $state = $this->getFeatureState($definition->id);
            if ($definition->dependsOn->contains($featureId) && $state !== null && $state->active) {
                $dependents[] = $definition->id;
            }
        }
        return FeatureIds::fromArray($dependents);
    }

    /**
     * @template TOptions of FeatureOptions
     * @param FeatureDefinition<TOptions> $definition
     * @return Feature<TOptions>
     */
    private function buildFeatureFromDefinition(FeatureDefinition $definition): Feature
    {
        $featureState = $this->getFeatureState($definition->id);
        $featureOptions = null;
        if ($featureState !== null && $definition->hasOptions()) {
            $featureOptions = $definition->parseOptions($featureState->options);
        }
        return new Feature(
            id: $definition->id,
            name: $definition->name,
            description: $definition->description,
            icon: $definition->icon,
            optionsClassName: $definition->optionsClassName,
            active: $featureState->active ?? false,
            options: $featureOptions,
            group: $definition->group,
            dependsOn: $definition->dependsOn,
            unmetDependencies: $this->unmetDependencies($definition->dependsOn),
            activeDependents: $this->activeDependents($definition->id),
        );
    }

    private function getFeatureStates(): FeatureStates
    {
        if ($this->featureStatesRuntimeCache === null) {
            $this->featureStatesRuntimeCache = $this->forStoringFeatureStates->loadAll();
        }
        return $this->featureStatesRuntimeCache;
    }

    private function getFeatureState(FeatureId $featureId): FeatureState|null
    {
        return $this->getFeatureStates()->get($featureId);
    }
}
