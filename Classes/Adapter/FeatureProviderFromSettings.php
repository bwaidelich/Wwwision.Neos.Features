<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Adapter;

use InvalidArgumentException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinition;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinitions;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroup;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroups;
use Wwwision\Neos\Features\Model\FeatureImplementation\ConfigurableFeatureImplementation;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementation;
use Wwwision\Neos\Features\Model\FeatureImplementation\NoopFeature;
use Wwwision\Neos\Features\Model\FeatureImplementation\OptionlessFeatureImplementation;
use Wwwision\Neos\Features\Ports\ForProvidingFeatureConfiguration;

/**
 * Builds the {@see FeatureGroups} and {@see FeatureDefinitions} from the package settings and validates the
 * resulting dependency graph eagerly (fail fast at load): unknown {@see FeatureDefinition::$dependsOn} references,
 * dependency cycles and unknown {@see FeatureDefinition::$group} references all raise an {@see InvalidArgumentException}.
 */
final class FeatureProviderFromSettings implements ForProvidingFeatureConfiguration
{
    private FeatureGroups|null $featureGroupsRuntimeCache = null;
    private FeatureDefinitions|null $featureDefinitionsRuntimeCache = null;

    /**
     * @param array<mixed> $featureSettings
     * @param array<mixed> $featureGroupSettings
     */
    public function __construct(
        private readonly array $featureSettings,
        private readonly array $featureGroupSettings,
        private readonly ObjectManagerInterface $objectManager,
    ) {}

    public function getFeatureGroups(): FeatureGroups
    {
        if ($this->featureGroupsRuntimeCache === null) {
            $this->load();
        }
        assert($this->featureGroupsRuntimeCache !== null);
        return $this->featureGroupsRuntimeCache;
    }

    public function getFeatureDefinitions(): FeatureDefinitions
    {
        if ($this->featureDefinitionsRuntimeCache === null) {
            $this->load();
        }
        assert($this->featureDefinitionsRuntimeCache !== null);
        return $this->featureDefinitionsRuntimeCache;
    }

    private function load(): void
    {
        $groups = $this->buildGroups();
        $definitions = $this->buildDefinitions();
        $this->validateGraph($definitions, $groups);
        $this->featureGroupsRuntimeCache = $groups;
        $this->featureDefinitionsRuntimeCache = $definitions;
    }

    private function buildGroups(): FeatureGroups
    {
        $groups = [];
        foreach (new PositionalArraySorter($this->featureGroupSettings)->toArray() as $groupId => $settings) {
            Assert::string($groupId, 'Feature group ID must be a string');
            Assert::isArray($settings, sprintf('Settings for feature group "%s" must be an array, given: %%s', $groupId));
            $name = $settings['name'] ?? $groupId;
            Assert::string($name, sprintf('Feature group "%s" name must be a string, given: %%s', $groupId));
            $description = $settings['description'] ?? null;
            Assert::nullOrString($description, sprintf('Feature group "%s" description must be a string, given: %%s', $groupId));
            $icon = $settings['icon'] ?? null;
            Assert::nullOrString($icon, sprintf('Feature group "%s" icon must be a string, given: %%s', $groupId));
            $groups[] = FeatureGroup::create(
                id: $groupId,
                name: $name,
                description: $description,
                icon: $icon,
            );
        }
        return FeatureGroups::fromArray($groups);
    }

    private function buildDefinitions(): FeatureDefinitions
    {
        $featureDefinitions = [];
        foreach (new PositionalArraySorter($this->featureSettings)->toArray() as $featureId => $settings) {
            Assert::string($featureId, 'Feature ID must be a string');
            Assert::isArray($settings, sprintf('Settings for feature "%s" must be an array, given: %%s', $featureId));
            if (!isset($settings['objectName'])) {
                $featureInstance = new NoopFeature();
            } else {
                Assert::string($settings['objectName'], sprintf('Feature "%s" must have a "objectName" setting of type string, given: %%s', $featureId));
                $featureInstance = $this->objectManager->get($settings['objectName']);
                Assert::isInstanceOf($featureInstance, FeatureImplementation::class, sprintf('"objectName" of Feature "%s" must implement %s', $featureId, FeatureImplementation::class));
            }

            $name = $settings['name'] ?? $featureId;
            Assert::string($name, sprintf('Feature "%s" name must be a string, given: %%s', $featureId));
            $description = $settings['description'] ?? null;
            Assert::nullOrString($description, sprintf('Feature "%s" description must be a string, given: %%s', $featureId));
            $icon = $settings['icon'] ?? null;
            Assert::nullOrString($icon, sprintf('Feature "%s" icon must be a string, given: %%s', $featureId));
            $group = $settings['group'] ?? null;
            Assert::nullOrString($group, sprintf('Feature "%s" group must be a string, given: %%s', $featureId));
            $dependsOn = $settings['dependsOn'] ?? [];
            if (is_string($dependsOn)) {
                $dependsOn = [$dependsOn];
            } else {
                Assert::isArray($dependsOn, sprintf('Feature "%s" dependsOn must be an array, given: %%s', $featureId));
                Assert::allString($dependsOn, sprintf('Feature "%s" dependsOn must be a list of feature IDs (strings), given: %%s', $featureId));
            }

            if ($featureInstance instanceof OptionlessFeatureImplementation) {
                $featureDefinitions[] = FeatureDefinition::createOptionless(
                    id: $featureId,
                    name: $name,
                    onActivate: $featureInstance->activate(...),
                    onDeactivate: $featureInstance->deactivate(...),
                    description: $description,
                    icon: $icon,
                    dependsOn: array_values($dependsOn),
                    group: $group,
                );
            } elseif ($featureInstance instanceof ConfigurableFeatureImplementation) {
                $featureDefinitions[] = FeatureDefinition::create(
                    id: $featureId,
                    name: $name,
                    optionsClassName: $featureInstance::optionsClassName(),
                    onActivate: $featureInstance->activate(...),
                    onUpdateOptions: $featureInstance->updateOptions(...),
                    onDeactivate: $featureInstance->deactivate(...),
                    description: $description,
                    icon: $icon,
                    dependsOn: array_values($dependsOn),
                    group: $group,
                );
            } else {
                throw new InvalidArgumentException(sprintf('"objectName" of Feature "%s" must implement %s or %s', $featureId, ConfigurableFeatureImplementation::class, OptionlessFeatureImplementation::class), 1780682591);
            }
        }
        return FeatureDefinitions::fromArray($featureDefinitions);
    }

    private function validateGraph(FeatureDefinitions $definitions, FeatureGroups $groups): void
    {
        foreach ($definitions as $definition) {
            if ($definition->group !== null && !$groups->has($definition->group)) {
                throw new InvalidArgumentException(sprintf('Feature "%s" references unknown group "%s"', $definition->id->value, $definition->group->value), 1748000100);
            }
            foreach ($definition->dependsOn as $dependencyId) {
                if ($definitions->get($dependencyId) === null) {
                    throw new InvalidArgumentException(sprintf('Feature "%s" depends on unknown feature "%s"', $definition->id->value, $dependencyId->value), 1748000101);
                }
            }
        }
        $this->detectCycles($definitions);
    }

    private function detectCycles(FeatureDefinitions $definitions): void
    {
        $resolved = [];
        foreach ($definitions as $definition) {
            $this->detectCyclesFrom($definition->id, [], $resolved, $definitions);
        }
    }

    /**
     * Depth-first search for a cycle reachable from the given feature.
     *
     * @param list<string> $path the feature ids currently on the traversal stack
     * @param array<string, true> $resolved ids that were already traversed without a cycle (accumulates across roots)
     */
    private function detectCyclesFrom(FeatureId $featureId, array $path, array &$resolved, FeatureDefinitions $definitions): void
    {
        $key = $featureId->value;
        if (isset($resolved[$key])) {
            return;
        }
        $index = array_search($key, $path, true);
        if ($index !== false) {
            $cycle = array_slice($path, $index);
            $cycle[] = $key;
            throw new InvalidArgumentException(sprintf('Feature dependency cycle detected: %s', implode(' -> ', $cycle)), 1748000102);
        }
        $path[] = $key;
        $definition = $definitions->get($featureId);
        if ($definition !== null) {
            foreach ($definition->dependsOn as $dependencyId) {
                $this->detectCyclesFrom($dependencyId, $path, $resolved, $definitions);
            }
        }
        $resolved[$key] = true;
    }
}
