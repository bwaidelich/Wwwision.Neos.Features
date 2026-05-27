<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Adapter;

use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\FeatureState\FeatureState;
use Wwwision\Neos\Features\Model\FeatureState\FeatureStates;
use Wwwision\Neos\Features\Ports\ForStoringFeatureStates;

final readonly class ForStoringFeatureStatesViaYaml implements ForStoringFeatureStates
{
    public function __construct(
        private string $yamlPath,
    ) {
        if (!is_dir(dirname($this->yamlPath))) {
            mkdir(dirname($this->yamlPath), 0777, true);
        }
        if (!file_exists($this->yamlPath)) {
            file_put_contents($this->yamlPath, '');
        }
    }

    public function store(FeatureState $featureState): void
    {
        $data = $this->load();
        $data[$featureState->featureId->value] = [
            'active' => $featureState->active,
            'options' => $featureState->options,
        ];
        $this->save($data);
    }

    public function remove(FeatureId $featureId): void
    {
        $data = $this->load();
        unset($data[$featureId->value]);
        $this->save($data);
    }

    public function loadAll(): FeatureStates
    {
        $featureStates = [];
        foreach ($this->load() as $featureId => $data) {
            Assert::isMap($data);
            Assert::nullOrIsMap($data['options']);
            $featureStates[] = new FeatureState(
                FeatureId::fromString($featureId),
                (bool) ($data['active'] ?? false),
                $data['options'] ?? [],
            );
        }
        return FeatureStates::fromArray($featureStates);
    }

    // ------------------------

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        $data = Yaml::parseFile($this->yamlPath) ?? [];
        Assert::isMap($data);
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function save(array $data): void
    {
        file_put_contents($this->yamlPath, Yaml::dump($data));
    }
}
