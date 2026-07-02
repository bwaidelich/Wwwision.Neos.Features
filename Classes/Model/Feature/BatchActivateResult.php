<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Model\Feature;

/**
 * Outcome of {@see \Wwwision\Neos\Features\FeatureSystem::activateFeatures()}:
 * - {@see $activated} contains the features that were activated, in activation (i.e. dependency) order
 * - {@see $skipped} contains the requested features that were already active and therefore skipped
 * - if a feature failed to activate, {@see $failedFeatureId} and {@see $failureMessage} describe the failure;
 *   features after the failed one were not processed, features in {@see $activated} remain active
 */
final readonly class BatchActivateResult
{
    private function __construct(
        public FeatureIds $activated,
        public FeatureIds $skipped,
        public FeatureId|null $failedFeatureId,
        public string|null $failureMessage,
    ) {}

    public static function success(FeatureIds $activated, FeatureIds $skipped): self
    {
        return new self($activated, $skipped, null, null);
    }

    public static function failure(FeatureIds $activated, FeatureIds $skipped, FeatureId $failedFeatureId, string $failureMessage): self
    {
        return new self($activated, $skipped, $failedFeatureId, $failureMessage);
    }

    public function hasFailure(): bool
    {
        return $this->failedFeatureId !== null;
    }
}
