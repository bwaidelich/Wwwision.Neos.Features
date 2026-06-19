<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureImplementation;

use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\Feature\FeatureUpdateOptionsResult;

/**
 * A feature implementation that takes typed options. Its lifecycle methods receive the (previously) configured options.
 *
 * @template TOptions of FeatureOptions
 */
interface ConfigurableFeatureImplementation extends FeatureImplementation
{
    /**
     * @return class-string<TOptions>
     */
    public static function optionsClassName(): string;

    /**
     * @param TOptions $options
     */
    public function activate(FeatureContext $context, FeatureOptions $options): FeatureActivateResult;

    /**
     * @param TOptions $previousOptions
     * @param TOptions $newOptions
     */
    public function updateOptions(FeatureContext $context, FeatureOptions $previousOptions, FeatureOptions $newOptions): FeatureUpdateOptionsResult;

    /**
     * @param TOptions $previousOptions
     */
    public function deactivate(FeatureContext $context, FeatureOptions $previousOptions): FeatureDeactivateResult;
}
