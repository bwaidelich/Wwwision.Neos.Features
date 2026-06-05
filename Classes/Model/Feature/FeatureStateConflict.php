<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\Feature;

use RuntimeException;

/**
 * Thrown when an activation or deactivation is a no-op because the feature is already in the target state:
 * - a feature that is already active cannot be activated again
 * - a feature that is already inactive cannot be deactivated again
 */
final class FeatureStateConflict extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly FeatureId $featureId,
        int $code,
    ) {
        parent::__construct($message, $code);
    }

    public static function cannotActivateBecauseAlreadyActive(FeatureId $featureId): self
    {
        return new self(
            sprintf('Feature "%s" cannot be activated because it is already active', $featureId->value),
            $featureId,
            1748000002,
        );
    }

    public static function cannotDeactivateBecauseAlreadyInactive(FeatureId $featureId): self
    {
        return new self(
            sprintf('Feature "%s" cannot be deactivated because it is not active', $featureId->value),
            $featureId,
            1748000003,
        );
    }

    public static function cannotUpdateOptionsBecauseInactive(FeatureId $featureId): self
    {
        return new self(
            sprintf('Options of Feature "%s" cannot be updated because it is not active', $featureId->value),
            $featureId,
            1780682587,
        );
    }
}
