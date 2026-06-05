<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureImplementation;

use Wwwision\Neos\Features\Model\Feature\EmptyFeatureOptions;
use Wwwision\Neos\Features\Model\Feature\FeatureActivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureDeactivateResult;
use Wwwision\Neos\Features\Model\Feature\FeatureOptions;
use Wwwision\Neos\Features\Model\Feature\FeatureUpdateOptionsResult;

/**
 * @implements FeatureImplementation<EmptyFeatureOptions>
 */
final readonly class NoopFeature implements FeatureImplementation
{
    public static function optionsClassName(): string
    {
        return EmptyFeatureOptions::class;
    }

    public function activate(FeatureOptions $options): FeatureActivateResult
    {
        // no op
        return FeatureActivateResult::success();
    }

    public function updateOptions(FeatureOptions $previousOptions, FeatureOptions $newOptions): FeatureUpdateOptionsResult
    {
        // no op
        return FeatureUpdateOptionsResult::success();
    }

    public function deactivate(FeatureOptions $previousOptions): FeatureDeactivateResult
    {
        // no op
        return FeatureDeactivateResult::success();
    }
}
