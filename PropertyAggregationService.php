<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;

class PropertyService
{
    private string $dataDirectory;
    private CacheItemPoolInterface $cache;
    private SerializerInterface $serializer;

    public function __construct(string $projectDir, CacheItemPoolInterface $cache, SerializerInterface $serializer)
    {
        $this->dataDirectory = $projectDir . '/public/data/';
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    public function getProperties(): JsonResponse
    {
        // Cache-Schlüssel konfigurierbar machen 
        $cacheKey = 'properties_merged';
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return new JsonResponse($cacheItem->get());
        }

        // Datenquellen konfigurierbar machen 
        $source1 = $this->loadData('source1.json');
        $source2 = $this->loadData('source2.json');

        if ($source1 === null && $source2 === null) {
            // Spezifischeren Fehlercode verwenden (z.B. 500)
            throw new HttpException(500, 'Failed to load data from both sources.');
        }

        $mergedData = $this->mergeData($source1 ?? [], $source2 ?? []);

        $cacheItem->set($mergedData);
        $this->cache->save($cacheItem);

        return new JsonResponse($mergedData);
    }

    private function loadData(string $filename): ?array
    {
        $filePath = $this->dataDirectory . $filename;

        if (!file_exists($filePath)) {
            //  Fehlercode verwenden 
            return null;
        }

        $fileContent = file_get_contents($filePath);

        if ($fileContent === false) {
            return null;
        }

        try {
            return json_decode($fileContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // Loggen des Fehlers für Debugging-Zwecke
            error_log('Error decoding JSON from ' . $filename . ': ' . $e->getMessage());
            return null;
        }
    }

    private function mergeData(array $source1, array $source2): array
    {
        $merged = [];

        foreach ($source1 as $item) {
            $merged[] = $this->normalizeItem($item, 'source1');
        }

        foreach ($source2 as $item) {
            $merged[] = $this->normalizeItem($item, 'source2');
        }

        return $merged;
    }

    private function normalizeItem(array $item, string $source): array
    {
        return [
            'id' => $item['id'] ?? null,
            'address' => $item['address'] ?? null,
            'price' => $item['price'] ?? null,
            'source' => $source,
        ];
    }
}
