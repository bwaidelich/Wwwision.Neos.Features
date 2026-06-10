<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Fixtures;

use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementation;
use Wwwision\Neos\Features\Model\FeatureImplementation\FeatureImplementationFactory;
use Wwwision\Neos\Features\Model\FeatureImplementation\NoopFeature;

/**
 * A {@see FeatureImplementationFactory} used in the test suite. It records the options it was given so tests can
 * assert that the factory options were passed through.
 */
final class SampleFeatureFactory implements FeatureImplementationFactory
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $receivedOptions = null;

    public function create(array $options): FeatureImplementation
    {
        $this->receivedOptions = $options;
        return new NoopFeature();
    }
}
