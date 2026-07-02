<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Adapter;

use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Configuration\ConfigurationManager;
use Wwwision\Neos\Features\Ports\ForFlushingCaches;

/**
 * Flushes the caches that feature changes can affect, without flushing session storage (so backend users stay
 * logged in): feature implementations write Settings/NodeTypes YAML overrides, which are baked into the Flow
 * configuration cache, the node type schema and the (node type dependent) Fusion caches and rendered content.
 */
final class ForFlushingCachesViaFlow implements ForFlushingCaches
{
    private const CACHE_IDENTIFIERS = [
        'Neos_Neos_NodeType_Schema',
        'Neos_Neos_Fusion',
        'Neos_Fusion_Content',
        'Neos_Fusion_ObjectTree',
        'Neos_Fusion_ParsePartials',
    ];

    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        private readonly CacheManager $cacheManager,
    ) {}

    public function flushCaches(): void
    {
        $this->configurationManager->flushConfigurationCache();
        foreach (self::CACHE_IDENTIFIERS as $cacheIdentifier) {
            if ($this->cacheManager->hasCache($cacheIdentifier)) {
                $this->cacheManager->getCache($cacheIdentifier)->flush();
            }
        }
    }
}
