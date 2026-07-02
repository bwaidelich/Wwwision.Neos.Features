<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Tests\Fixtures;

use Wwwision\Neos\Features\Ports\ForFlushingCaches;

/**
 * Spy implementation of {@see ForFlushingCaches} for use in tests, counting the number of flushes.
 */
final class SpyCacheFlusher implements ForFlushingCaches
{
    public int $flushCount = 0;

    public function flushCaches(): void
    {
        $this->flushCount++;
    }
}
