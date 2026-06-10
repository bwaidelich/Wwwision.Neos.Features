<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureImplementation;

use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;

/**
 * The built-in optionless implementation used when a feature declares no `objectName`. Does nothing on activate/deactivate.
 */
final readonly class NoopFeature implements OptionlessFeatureImplementation
{
    public function activate(): FeatureActivateResult
    {
        // no op
        return FeatureActivateResult::success();
    }

    public function deactivate(): FeatureDeactivateResult
    {
        // no op
        return FeatureDeactivateResult::success();
    }
}
