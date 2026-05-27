<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features;

use InvalidArgumentException;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\Feature\Feature;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\Feature\Features;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinition;
use Wwwision\Neos\Features\Model\FeatureState\FeatureState;
use Wwwision\Neos\Features\Model\FeatureState\FeatureStates;
use Wwwision\Neos\Features\Ports\ForProvidingFeatureDefinitions;
use Wwwision\Neos\Features\Ports\ForStoringFeatureStates;
use Wwwision\Types\Normalizer\Normalizer;
use Wwwision\Types\Options;
use Wwwision\Types\Parser;
use Wwwision\Types\Schema\ShapeSchema;

final class FeatureSystem
{
    private FeatureStates|null $featureStatesRuntimeCache = null;

    public function __construct(
        private readonly ForProvidingFeatureDefinitions $forProvidingFeatureDefinitions,
        private readonly ForStoringFeatureStates $forStoringFeatureStates,
    ) {}

    public function getFeatures(): Features
    {
        return Features::fromArray(
            $this->forProvidingFeatureDefinitions->getFeatureDefinitions()->map($this->buildFeatureFromDefinition(...)),
        );
    }

    /**
     * @return Feature<FeatureOptions>
     */
    public function getFeature(FeatureId $featureId): Feature
    {
        $featureDefinition = $this->forProvidingFeatureDefinitions->getFeatureDefinitions()->get($featureId);
        if ($featureDefinition === null) {
            throw new InvalidArgumentException(sprintf('Feature with id "%s" does not exist', $featureId), 1779026887);
        }
        return $this->buildFeatureFromDefinition($featureDefinition);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function activateFeature(FeatureId $featureId, array $options): void
    {
        $featureDefinition = $this->forProvidingFeatureDefinitions->getFeatureDefinitions()->get($featureId);
        if ($featureDefinition === null) {
            throw new InvalidArgumentException(sprintf('Feature with id "%s" does not exist', $featureId), 1779121538);
        }
        $optionsSchema = Parser::getSchema($featureDefinition->optionsClassName);
        Assert::isInstanceOf($optionsSchema, ShapeSchema::class);
        $featureOptions = $optionsSchema->instantiate($options, Options::create(ignoreUnrecognizedKeys: false));
        Assert::isInstanceOf($featureOptions, FeatureOptions::class);
        // TODO: Evaluate result
        ($featureDefinition->onActivate)($featureOptions);

        $normalizedOptions = new Normalizer()->normalize($featureOptions);
        Assert::isMap($normalizedOptions);
        $this->forStoringFeatureStates->store(new FeatureState($featureId, true, $normalizedOptions));
    }

    public function deactivateFeature(FeatureId $featureId, bool $removeState = false): void
    {
        $featureDefinition = $this->forProvidingFeatureDefinitions->getFeatureDefinitions()->get($featureId);
        if ($featureDefinition === null) {
            throw new InvalidArgumentException(sprintf('Feature with id "%s" does not exist', $featureId), 1779122634);
        }
        // TODO: Evaluate result
        ($featureDefinition->onDeactivate)();
        if ($removeState) {
            $this->forStoringFeatureStates->remove($featureId);
        } else {
            $featureState = $this->forStoringFeatureStates->loadAll()->get($featureId);
            if ($featureState?->active) {
                $this->forStoringFeatureStates->store($featureState->with(active: false));
            }
        }
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
