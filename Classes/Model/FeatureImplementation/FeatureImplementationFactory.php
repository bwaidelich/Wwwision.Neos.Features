<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureImplementation;

/**
 * Builds a {@see FeatureImplementation} from its factory options, letting one implementation be reused across features
 * with different parameters. Bound to a feature via the `factoryClassName` setting (an alternative to `objectName`) and
 * resolved through the object manager, so it may declare its own dependencies.
 *
 * The factory options are the raw `options` array from Settings; a factory may parse them into a value object itself.
 */
interface FeatureImplementationFactory
{
    /**
     * @param array<string, mixed> $options the feature's `options` from Settings
     */
    public function create(array $options): FeatureImplementation;
}
