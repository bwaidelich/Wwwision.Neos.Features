<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features;

use InvalidArgumentException;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\Feature\Feature;
use Wwwision\Neos\Features\Model\Feature\FeatureDependencyViolation;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\FeatureIds;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\Feature\Features;
use Wwwision\Neos\Features\Model\Feature\FeatureStateConflict;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinition;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroups;
use Wwwision\Neos\Features\Model\FeatureState\FeatureState;
use Wwwision\Neos\Features\Model\FeatureState\FeatureStates;
use Wwwision\Neos\Features\Ports\ForProvidingFeatureConfiguration;
use Wwwision\Neos\Features\Ports\ForStoringFeatureStates;
use Wwwision\Types\Normalizer\Normalizer;
use Wwwision\Types\Options;
use Wwwision\Types\Parser;
use Wwwision\Types\Schema\ShapeSchema;

final class FeatureSystem
{
    private FeatureStates|null $featureStatesRuntimeCache = null;

    public function __construct(
        private readonly ForProvidingFeatureConfiguration $forProvidingFeatureConfiguration,
        private readonly ForStoringFeatureStates $forStoringFeatureStates,
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
        $featureDefinition = $this->requireDefinition($featureId, 1779121538);
        if ($this->getFeatureState($featureId)?->active === true) {
            throw FeatureStateConflict::cannotActivateBecauseAlreadyActive($featureId);
        }
        $inactiveDependencies = $this->unmetDependencies($featureDefinition->dependsOn);
        if (!$inactiveDependencies->isEmpty()) {
            throw FeatureDependencyViolation::cannotActivateBecauseDependenciesInactive($featureId, $inactiveDependencies);
        }
        $optionsSchema = Parser::getSchema($featureDefinition->optionsClassName);
        Assert::isInstanceOf($optionsSchema, ShapeSchema::class);
        $featureOptions = $optionsSchema->instantiate($options, Options::create(ignoreUnrecognizedKeys: false));
        Assert::isInstanceOf($featureOptions, FeatureOptions::class);
        // TODO: Evaluate result
        ($featureDefinition->onActivate)($featureOptions);

        $normalizedOptions = new Normalizer()->normalize($featureOptions);
        Assert::isMap($normalizedOptions);
        $this->storeAndInvalidate(new FeatureState($featureId, true, $normalizedOptions));
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
        ($featureDefinition->onDeactivate)();
        if ($removeState) {
            $this->forStoringFeatureStates->remove($featureId);
            $this->featureStatesRuntimeCache = null;
        } else {
            $this->storeAndInvalidate($currentState->with(active: false));
        }
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
        if ($featureState !== null) {
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
