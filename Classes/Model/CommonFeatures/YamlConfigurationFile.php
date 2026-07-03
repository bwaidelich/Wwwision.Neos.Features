<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\CommonFeatures;

use Neos\Utility\Files;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * Wraps a YAML configuration file, providing nested set/unset operations that clean up empty parent arrays on removal.
 */
final readonly class YamlConfigurationFile
{
    public function __construct(
        private string $filePath,
    ) {}

    public static function forFlowConfiguration(string $fileName): self
    {
        return new self(Files::concatenatePaths([FLOW_PATH_CONFIGURATION, $fileName])); // @phpstan-ignore constant.notFound
    }

    /**
     * @param non-empty-list<string> $keyPath
     */
    public function set(array $keyPath, mixed $value): void
    {
        $data = $this->read();
        self::setNested($data, $keyPath, $value);
        $this->write($data);
    }

    /**
     * @param non-empty-list<string> $keyPath
     */
    public function unset(array $keyPath): void
    {
        $data = $this->read();
        self::unsetNested($data, $keyPath);
        $this->write($data);
    }

    /**
     * Sets multiple entries in a single read/write cycle.
     *
     * @param list<array{non-empty-list<string>, mixed}> $entries
     */
    public function setMany(array $entries): void
    {
        $data = $this->read();
        foreach ($entries as [$keyPath, $value]) {
            self::setNested($data, $keyPath, $value);
        }
        $this->write($data);
    }

    /**
     * Removes multiple entries in a single read/write cycle, cleaning up empty parent arrays after each removal.
     *
     * @param list<non-empty-list<string>> $keyPaths
     */
    public function unsetMany(array $keyPaths): void
    {
        $data = $this->read();
        foreach ($keyPaths as $keyPath) {
            self::unsetNested($data, $keyPath);
        }
        $this->write($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function read(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }
        $data = Yaml::parseFile($this->filePath) ?? [];
        Assert::isMap($data, sprintf('Expected a map in "%s", given: %%s', $this->filePath));
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function write(array $data): void
    {
        file_put_contents($this->filePath, Yaml::dump($data, 10, 2));
    }

    /**
     * @param array<string, mixed> $data
     * @param non-empty-list<string> $keyPath
     */
    private static function setNested(array &$data, array $keyPath, mixed $value): void
    {
        $key = array_shift($keyPath);
        if ($keyPath === []) {
            $data[$key] = $value;
            return;
        }
        if (!isset($data[$key]) || !self::isMap($data[$key])) {
            $data[$key] = [];
        }
        self::setNested($data[$key], $keyPath, $value);
    }

    /**
     * @param array<string, mixed> $data
     * @param non-empty-list<string> $keyPath
     */
    private static function unsetNested(array &$data, array $keyPath): void
    {
        $key = array_shift($keyPath);
        if ($keyPath === []) {
            unset($data[$key]);
            return;
        }
        if (!isset($data[$key]) || !self::isMap($data[$key])) {
            return;
        }
        self::unsetNested($data[$key], $keyPath);
        if ($data[$key] === []) {
            unset($data[$key]);
        }
    }

    /**
     * @phpstan-assert-if-true array<string, mixed> $value
     */
    private static function isMap(mixed $value): bool
    {
        return is_array($value) && array_all($value, static fn(mixed $v, mixed $key): bool => is_string($key));
    }
}
