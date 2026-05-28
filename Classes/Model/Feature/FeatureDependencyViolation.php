<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\Feature;

use RuntimeException;

/**
 * Thrown when an activation or deactivation is blocked because of the {@see FeatureDefinition::$dependsOn} relationship:
 * - a feature cannot be activated while one of its dependencies is inactive
 * - a feature cannot be deactivated while one of its active dependents still requires it
 */
final class FeatureDependencyViolation extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly FeatureId $featureId,
        public readonly FeatureIds $offendingFeatureIds,
        int $code,
    ) {
        parent::__construct($message, $code);
    }

    public static function cannotActivateBecauseDependenciesInactive(FeatureId $featureId, FeatureIds $inactiveDependencies): self
    {
        return new self(
            sprintf(
                'Feature "%s" cannot be activated because the following required feature(s) are not active: %s',
                $featureId->value,
                implode(', ', $inactiveDependencies->toStringArray()),
            ),
            $featureId,
            $inactiveDependencies,
            1748000000,
        );
    }

    public static function cannotDeactivateBecauseRequiredByActiveDependents(FeatureId $featureId, FeatureIds $activeDependents): self
    {
        return new self(
            sprintf(
                'Feature "%s" cannot be deactivated because it is still required by the following active feature(s): %s',
                $featureId->value,
                implode(', ', $activeDependents->toStringArray()),
            ),
            $featureId,
            $activeDependents,
            1748000001,
        );
    }
}
