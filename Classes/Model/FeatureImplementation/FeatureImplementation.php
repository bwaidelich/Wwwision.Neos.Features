<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureImplementation;

use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\Feature\FeatureUpdateOptionsResult;

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
     * @param TOptions $options
     */
    public function activate(FeatureOptions $options): FeatureActivateResult;

    /**
     * @param TOptions $previousOptions
     * @param TOptions $newOptions
     */
    public function updateOptions(FeatureOptions $previousOptions, FeatureOptions $newOptions): FeatureUpdateOptionsResult;

    /**
     * @param TOptions $previousOptions
     */
    public function deactivate(FeatureOptions $previousOptions): FeatureDeactivateResult;

}
