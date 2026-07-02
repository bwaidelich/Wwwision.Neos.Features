<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Ports;

/**
 * Invoked by the {@see \Wwwision\Neos\Features\FeatureSystem} whenever the state or options of at least one feature
 * changed, so that caches that (may) depend on feature configuration can be invalidated.
 *
 * Batch operations invoke this only once, after the last feature was processed.
 */
interface ForFlushingCaches
{
    public function flushCaches(): void;
}
