<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Cache;

use Farzai\JsonSerializer\Metadata\ClassMetadata;
use Farzai\JsonSerializer\Metadata\MetadataFactory;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache for class metadata.
 *
 * Provides in-memory caching with optional PSR-16 backing cache.
 */
class MetadataCache
{
    /**
     * @var array<class-string, ClassMetadata>
     */
    private array $memoryCache = [];

    private readonly MetadataFactory $factory;

    public function __construct(
        private readonly ?CacheInterface $cache = null
    ) {
        $this->factory = new MetadataFactory;
    }

    /**
     * Get metadata for a class.
     *
     * @param  class-string  $className
     */
    public function get(string $className): ClassMetadata
    {
        // Check memory cache first
        if (isset($this->memoryCache[$className])) {
            return $this->memoryCache[$className];
        }

        // Check PSR-16 cache
        if ($this->cache !== null) {
            $cacheKey = $this->getCacheKey($className);
            $cached = $this->cache->get($cacheKey);

            if ($cached instanceof ClassMetadata) {
                $this->memoryCache[$className] = $cached;

                return $cached;
            }
        }

        // Create new metadata
        $metadata = $this->factory->createForClass($className);

        // Store in caches
        $this->memoryCache[$className] = $metadata;

        if ($this->cache !== null) {
            $cacheKey = $this->getCacheKey($className);
            $this->cache->set($cacheKey, $metadata);
        }

        return $metadata;
    }

    /**
     * Get cache key for a class.
     *
     * @param  class-string  $className
     */
    private function getCacheKey(string $className): string
    {
        return 'json_serializer.metadata.'.str_replace('\\', '_', $className);
    }
}
