<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\FeatureImplementation;

/**
 * Marker for the PHP object that runs a feature's lifecycle side effects.
 *
 * Implement one of the two subtypes, never this interface directly:
 * - {@see ConfigurableFeatureImplementation} for features that take typed options
 * - {@see OptionlessFeatureImplementation} for features that take no options
 */
interface FeatureImplementation {}
