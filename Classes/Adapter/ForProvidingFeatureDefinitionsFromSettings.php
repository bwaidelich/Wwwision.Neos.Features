<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Adapter;

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinition;
use Wwwision\Neos\Features\Model\FeatureDefinition\FeatureDefinitions;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementation;
use Wwwision\Neos\Features\Model\FeatureImplementation\NoopFeature;
use Wwwision\Neos\Features\Ports\ForProvidingFeatureDefinitions;

final readonly class ForProvidingFeatureDefinitionsFromSettings implements ForProvidingFeatureDefinitions
{
    /**
     * @param array<mixed> $featureSettings
     */
    public function __construct(
        private array $featureSettings,
        private ObjectManagerInterface $objectManager,
    ) {}

    public function getFeatureDefinitions(): FeatureDefinitions
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

            Assert::nullOrString($settings['name'], sprintf('Feature "%s" name must be a string, given: %%s', $featureId));
            Assert::nullOrString($settings['description'], sprintf('Feature "%s" description must be a string, given: %%s', $featureId));
            Assert::nullOrString($settings['icon'], sprintf('Feature "%s" icon must be a string, given: %%s', $featureId));
            $featureDefinitions[] = FeatureDefinition::create(
                id: $featureId,
                name: $settings['name'] ?? $featureId,
                optionsClassName: $featureInstance::optionsClassName(),
                onActivate: $featureInstance->activate(...),
                onDeactivate: $featureInstance->deactivate(...),
                description: $settings['description'] ?? null,
                icon: $settings['icon'] ?? null,
            );
        }
        return FeatureDefinitions::fromArray($featureDefinitions);
    }
}
