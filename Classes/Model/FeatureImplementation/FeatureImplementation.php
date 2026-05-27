<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureImplementation;

use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;

/**
 * @template TOptions of FeatureOptions
 */
interface FeatureImplementation
{
    /**
     * @return class-string<TOptions>
     */
    public static function optionsClassName(): string;

    /**
     * @param FeatureOptions $options
     */
    public function activate(FeatureOptions $options): FeatureActivateResult;

    public function deactivate(): FeatureDeactivateResult;

}
