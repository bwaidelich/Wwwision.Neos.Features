<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Fixtures;

use Wwwision\Neos\Features\Model\Feature\FeatureOptions;

/**
 * A shape-based {@see FeatureOptions} implementation used to exercise option
 * (de-)serialization in the test suite.
 */
final readonly class SampleFeatureOptions implements FeatureOptions
{
    public function __construct(
        public string $message,
        public int $threshold = 0,
    ) {}
}
