<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureImplementation;

use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;

/**
 * A feature implementation that takes no options. There is nothing to configure, hence no options class and,
 * deliberately, no {@see ConfigurableFeatureImplementation::updateOptions()} - an optionless feature cannot be updated.
 */
interface OptionlessFeatureImplementation extends FeatureImplementation
{
    public function activate(FeatureContext $context): FeatureActivateResult;

    public function deactivate(FeatureContext $context): FeatureDeactivateResult;
}
