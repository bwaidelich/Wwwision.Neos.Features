<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Model\CommonFeatures;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Wwwision\Neos\Features\Model\CommonFeatures\YamlConfigurationFile;

#[CoversClass(YamlConfigurationFile::class)]
final class YamlConfigurationFileTest extends TestCase
{
    private string $filePath;

    protected function setUp(): void
    {
        $this->filePath = sys_get_temp_dir() . '/wwwision-neos-features-test-' . uniqid('', true) . '.yaml';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }

    private function file(): YamlConfigurationFile
    {
        return new YamlConfigurationFile($this->filePath);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function givenFileContains(array $data): void
    {
        file_put_contents($this->filePath, Yaml::dump($data, 10, 2));
    }

    private function fileContents(): mixed
    {
        self::assertFileExists($this->filePath);
        return Yaml::parseFile($this->filePath);
    }

    // ------------------------ set ------------------------

    public function test_set_creates_the_file_if_it_does_not_exist(): void
    {
        $this->file()->set(['key'], 'value');

        self::assertSame(['key' => 'value'], $this->fileContents());
    }

    public function test_set_creates_intermediate_maps_for_a_nested_key_path(): void
    {
        $this->file()->set(['a', 'b', 'c'], 'value');

        self::assertSame(['a' => ['b' => ['c' => 'value']]], $this->fileContents());
    }

    public function test_set_preserves_unrelated_content(): void
    {
        $this->givenFileContains(['other' => ['keep' => 'me'], 'a' => ['keep' => true]]);

        $this->file()->set(['a', 'b'], 'value');

        self::assertSame(['other' => ['keep' => 'me'], 'a' => ['keep' => true, 'b' => 'value']], $this->fileContents());
    }

    public function test_set_overwrites_an_existing_leaf_value(): void
    {
        $this->givenFileContains(['a' => ['b' => 'old']]);

        $this->file()->set(['a', 'b'], 'new');

        self::assertSame(['a' => ['b' => 'new']], $this->fileContents());
    }

    public function test_set_replaces_a_scalar_intermediate_value_with_a_map(): void
    {
        $this->givenFileContains(['a' => 'scalar']);

        $this->file()->set(['a', 'b'], 'value');

        self::assertSame(['a' => ['b' => 'value']], $this->fileContents());
    }

    public function test_set_replaces_a_list_intermediate_value_with_a_map(): void
    {
        $this->givenFileContains(['a' => ['one', 'two']]);

        $this->file()->set(['a', 'b'], 'value');

        self::assertSame(['a' => ['b' => 'value']], $this->fileContents());
    }

    public function test_set_treats_an_empty_file_like_an_empty_map(): void
    {
        file_put_contents($this->filePath, '');

        $this->file()->set(['key'], 'value');

        self::assertSame(['key' => 'value'], $this->fileContents());
    }

    public function test_set_throws_if_the_file_does_not_contain_a_map(): void
    {
        file_put_contents($this->filePath, Yaml::dump(['one', 'two']));

        $this->expectException(InvalidArgumentException::class);
        $this->file()->set(['key'], 'value');
    }

    // ------------------------ unset ------------------------

    public function test_unset_removes_a_leaf_key(): void
    {
        $this->givenFileContains(['a' => ['b' => 'value', 'keep' => true]]);

        $this->file()->unset(['a', 'b']);

        self::assertSame(['a' => ['keep' => true]], $this->fileContents());
    }

    public function test_unset_removes_parents_that_become_empty(): void
    {
        $this->givenFileContains(['a' => ['b' => ['c' => 'value']], 'keep' => true]);

        $this->file()->unset(['a', 'b', 'c']);

        self::assertSame(['keep' => true], $this->fileContents());
    }

    public function test_unset_keeps_parents_that_still_hold_other_keys(): void
    {
        $this->givenFileContains(['a' => ['b' => ['c' => 'value'], 'keep' => true]]);

        $this->file()->unset(['a', 'b', 'c']);

        self::assertSame(['a' => ['keep' => true]], $this->fileContents());
    }

    public function test_unset_is_a_noop_for_a_missing_key_path(): void
    {
        $this->givenFileContains(['a' => ['keep' => true]]);

        $this->file()->unset(['a', 'missing', 'deeper']);

        self::assertSame(['a' => ['keep' => true]], $this->fileContents());
    }

    public function test_unset_is_a_noop_when_the_key_path_traverses_a_scalar_value(): void
    {
        $this->givenFileContains(['a' => 'scalar']);

        $this->file()->unset(['a', 'b']);

        self::assertSame(['a' => 'scalar'], $this->fileContents());
    }

    public function test_unset_is_a_noop_when_the_key_path_traverses_a_list(): void
    {
        $this->givenFileContains(['a' => ['one', 'two']]);

        $this->file()->unset(['a', 'b']);

        self::assertSame(['a' => ['one', 'two']], $this->fileContents());
    }

    public function test_unsetting_the_last_remaining_key_leaves_an_empty_map(): void
    {
        $this->givenFileContains(['a' => ['b' => 'value']]);

        $this->file()->unset(['a', 'b']);

        self::assertSame([], $this->fileContents());
    }

    // ------------------------ setMany / unsetMany ------------------------

    public function test_setMany_sets_all_given_entries(): void
    {
        $this->givenFileContains(['keep' => true]);

        $this->file()->setMany([
            [['a', 'b'], 'first'],
            [['c'], 'second'],
        ]);

        self::assertSame(['keep' => true, 'a' => ['b' => 'first'], 'c' => 'second'], $this->fileContents());
    }

    public function test_unsetMany_removes_all_given_key_paths_and_cleans_up_empty_parents(): void
    {
        $this->givenFileContains(['a' => ['b' => 'first'], 'c' => 'second', 'keep' => true]);

        $this->file()->unsetMany([
            ['a', 'b'],
            ['c'],
        ]);

        self::assertSame(['keep' => true], $this->fileContents());
    }
}
