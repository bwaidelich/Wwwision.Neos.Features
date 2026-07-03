<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Neos\Features\Adapter\ForStoringFeatureStatesViaYaml;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\FeatureState\FeatureState;
use Wwwision\Neos\Features\Model\FeatureState\FeatureStates;

#[CoversClass(ForStoringFeatureStatesViaYaml::class)]
#[UsesClass(FeatureId::class)]
#[UsesClass(FeatureState::class)]
#[UsesClass(FeatureStates::class)]
final class ForStoringFeatureStatesViaYamlTest extends TestCase
{
    private string $yamlPath;

    protected function setUp(): void
    {
        $this->yamlPath = sys_get_temp_dir() . '/wwwision-neos-features-test-' . uniqid('', true) . '/states.yaml';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->yamlPath)) {
            unlink($this->yamlPath);
        }
        $dir = dirname($this->yamlPath);
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    private function adapter(): ForStoringFeatureStatesViaYaml
    {
        return new ForStoringFeatureStatesViaYaml($this->yamlPath);
    }

    public function test_the_constructor_creates_the_target_directory_and_file(): void
    {
        $this->adapter();

        self::assertFileExists($this->yamlPath);
    }

    public function test_loadAll_returns_an_empty_collection_for_a_fresh_file(): void
    {
        self::assertSame([], iterator_to_array($this->adapter()->loadAll()));
    }

    public function test_store_and_loadAll_round_trip_a_state(): void
    {
        $adapter = $this->adapter();
        $adapter->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'hi', 'threshold' => 4]));

        $state = $adapter->loadAll()->get(FeatureId::fromString('a'));

        self::assertNotNull($state);
        self::assertTrue($state->active);
        self::assertSame(['message' => 'hi', 'threshold' => 4], $state->options);
    }

    public function test_store_overwrites_an_existing_state_for_the_same_feature(): void
    {
        $adapter = $this->adapter();
        $adapter->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'first']));
        $adapter->store(new FeatureState(FeatureId::fromString('a'), false, ['message' => 'second']));

        $states = iterator_to_array($adapter->loadAll());
        self::assertCount(1, $states);
        $state = $adapter->loadAll()->get(FeatureId::fromString('a'));
        self::assertNotNull($state);
        self::assertFalse($state->active);
        self::assertSame(['message' => 'second'], $state->options);
    }

    public function test_remove_deletes_a_stored_state(): void
    {
        $adapter = $this->adapter();
        $adapter->store(new FeatureState(FeatureId::fromString('a'), true, []));
        $adapter->store(new FeatureState(FeatureId::fromString('b'), true, []));

        $adapter->remove(FeatureId::fromString('a'));

        self::assertNull($adapter->loadAll()->get(FeatureId::fromString('a')));
        self::assertNotNull($adapter->loadAll()->get(FeatureId::fromString('b')));
    }

    public function test_remove_is_a_noop_for_an_unknown_feature(): void
    {
        $adapter = $this->adapter();
        $adapter->store(new FeatureState(FeatureId::fromString('a'), true, []));

        $adapter->remove(FeatureId::fromString('missing'));

        self::assertNotNull($adapter->loadAll()->get(FeatureId::fromString('a')));
    }

    public function test_stored_states_are_persisted_across_adapter_instances(): void
    {
        $this->adapter()->store(new FeatureState(FeatureId::fromString('a'), true, ['message' => 'persisted']));

        $state = $this->adapter()->loadAll()->get(FeatureId::fromString('a'));

        self::assertNotNull($state);
        self::assertSame(['message' => 'persisted'], $state->options);
    }
}
