<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Mapping;

use Psr\Cache\CacheItemPoolInterface;

trait MetadataCacheAwareTrait
{
    private CacheItemPoolInterface $cacheItemPool;

    public function setCacheItemPool(CacheItemPoolInterface $cacheItemPool): void
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    private function getCacheKeyForClassName(string $className): string
    {
        $className = str_replace('\\', '_', $className);

        return \sprintf('stichtingsd_sdmtd_%s', $className);
    }

    public function hasCachedMetadataForClass(string $className): bool
    {
        $key = $this->getCacheKeyForClassName($className);
        $item = $this->cacheItemPool->getItem($key);

        return $item->isHit();
    }

    private function addPropertyToMetadataCache(string $className, string $propertyName, array $propertyMetadata): void
    {
        $item = $this->getCachedMetadataForClass($className);
        $item[$propertyName] = $propertyMetadata;
        $this->setMetadataCacheForClass($className, $item);
    }

    public function setMetadataCacheForClass(string $className, array $metaData): void
    {
        $key = $this->getCacheKeyForClassName($className);
        $item = $this->cacheItemPool->getItem($key);
        $item->set(json_encode($metaData));
        $this->cacheItemPool->save($item);
    }

    public function getCachedMetadataForClass(string $className): array
    {
        $key = $this->getCacheKeyForClassName($className);
        $item = $this->cacheItemPool->getItem($key);

        $content = $item->get();
        if (!\is_string($content)) {
            return [];
        }

        if (!$item->isHit()) {
            return [];
        }

        $json = json_decode($content, true);
        if (!\is_array($json)) {
            return [];
        }

        return $json;
    }
}
