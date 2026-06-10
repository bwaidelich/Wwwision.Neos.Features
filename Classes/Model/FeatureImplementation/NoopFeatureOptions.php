<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureImplementation;

use Wwwision\Neos\Features\Model\Feature\FeatureOptions;

final readonly class NoopFeatureOptions implements FeatureOptions
{
    public function __construct() {}
}
