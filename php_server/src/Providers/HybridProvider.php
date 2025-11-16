<?php

namespace KindleWeather\Providers;

use KindleWeather\Models\WeatherData;
use KindleWeather\Services\Logger;

/**
 * Hybrid Weather Provider
 *
 * Combines data from multiple providers:
 * - Forecast data from OpenWeatherMap (better forecast coverage)
 * - Raincast data from Météo France (accurate short-term precipitation)
 *
 * This gives the best of both worlds: reliable forecasts from OWM
 * and precise rain predictions from Météo France.
 */
class HybridProvider implements WeatherProvider
{
    private OpenWeatherMapProvider $openWeatherMapProvider;
    private MeteoFranceProvider $meteoFranceProvider;

    /**
     * @param string $openWeatherMapApiKey OpenWeatherMap API key
     * @param string $meteoFranceToken Météo France API token
     */
    public function __construct(string $openWeatherMapApiKey, string $meteoFranceToken)
    {
        $this->openWeatherMapProvider = new OpenWeatherMapProvider($openWeatherMapApiKey);
        $this->meteoFranceProvider = new MeteoFranceProvider($meteoFranceToken);

        Logger::info("HybridProvider initialized (OWM for forecasts + Météo France for raincast)");
    }

    /**
     * Fetch weather data by combining both providers
     */
    public function fetchWeatherData(): WeatherData
    {
        Logger::info("Fetching hybrid weather data...");

        // Get forecast data from OpenWeatherMap
        Logger::info("Fetching forecast data from OpenWeatherMap...");
        $owmData = $this->openWeatherMapProvider->fetchWeatherData();

        // Get precipitation data from Météo France
        Logger::info("Fetching raincast data from Météo France...");
        try {
            $meteoData = $this->meteoFranceProvider->fetchWeatherData();
            $precipitations = $meteoData->precipitations;
            Logger::info("Raincast data successfully fetched from Météo France");
        } catch (\Exception $e) {
            // If Météo France fails, continue without precipitation data
            Logger::warning("Failed to fetch raincast from Météo France: " . $e->getMessage());
            $precipitations = null;
        }

        // Combine the data: OWM forecasts + Météo France precipitations
        return new WeatherData(
            $owmData->lastUpdateDate,
            $owmData->lastUpdateTime,
            $owmData->lastUpdateTimestamp,
            $precipitations, // From Météo France (or null if failed)
            $owmData->forecasts // From OpenWeatherMap
        );
    }

    /**
     * Get cached weather data with automatic refresh
     */
    public function getWeatherData(bool $forceRefresh = false): WeatherData
    {
        // Use 'hybrid' as cache identifier
        $cacheIdentifier = 'hybrid';

        // Check if we need to refresh
        if ($forceRefresh || $this->shouldRefreshCache($cacheIdentifier)) {
            try {
                // Fetch fresh data
                $weatherData = $this->fetchWeatherData();

                // Save to cache
                $this->saveToCache($cacheIdentifier, $weatherData);

                return $weatherData;
            } catch (\Exception $e) {
                Logger::error("Failed to fetch hybrid weather data: " . $e->getMessage());

                // Try to load from cache as fallback
                $cachedData = $this->loadFromCache($cacheIdentifier);
                if ($cachedData) {
                    Logger::info("Using stale cache as fallback for hybrid provider");
                    return $cachedData;
                }

                // No cache available, rethrow exception
                throw $e;
            }
        }

        // Load from cache
        $cachedData = $this->loadFromCache($cacheIdentifier);
        if ($cachedData) {
            Logger::info("Using cached hybrid weather data");
            return $cachedData;
        }

        // Cache miss, fetch fresh data
        Logger::info("Cache miss, fetching fresh hybrid data");
        return $this->getWeatherData(true);
    }

    /**
     * Check if cache should be refreshed
     */
    private function shouldRefreshCache(string $cacheIdentifier): bool
    {
        $lastUpdateFile = $this->getCacheFilePath($cacheIdentifier, 'last_update');

        if (!file_exists($lastUpdateFile)) {
            return true;
        }

        $lastUpdate = (int) file_get_contents($lastUpdateFile);
        $timeSinceUpdate = time() - $lastUpdate;
        $cacheExpirySeconds = \KindleWeather\Config\Config::CACHE_DURATION_MINUTES * 60;

        return $timeSinceUpdate >= $cacheExpirySeconds;
    }

    /**
     * Save weather data to cache
     */
    private function saveToCache(string $cacheIdentifier, WeatherData $data): void
    {
        $cacheFile = $this->getCacheFilePath($cacheIdentifier, 'weather');
        $lastUpdateFile = $this->getCacheFilePath($cacheIdentifier, 'last_update');

        // Save weather data as JSON
        $jsonData = json_encode([
            'lastUpdateDate' => $data->lastUpdateDate,
            'lastUpdateTime' => $data->lastUpdateTime,
            'lastUpdateTimestamp' => $data->lastUpdateTimestamp,
            'precipitations' => $data->precipitations,
            'forecasts' => $data->forecasts,
        ], JSON_PRETTY_PRINT);

        file_put_contents($cacheFile, $jsonData);
        file_put_contents($lastUpdateFile, (string) time());

        Logger::info("Hybrid weather data cached successfully");
    }

    /**
     * Load weather data from cache
     */
    private function loadFromCache(string $cacheIdentifier): ?WeatherData
    {
        $cacheFile = $this->getCacheFilePath($cacheIdentifier, 'weather');

        if (!file_exists($cacheFile)) {
            return null;
        }

        $jsonData = file_get_contents($cacheFile);
        $data = json_decode($jsonData, true);

        if (!$data) {
            Logger::warning("Failed to decode cached hybrid data");
            return null;
        }

        return new WeatherData(
            $data['lastUpdateDate'] ?? '',
            $data['lastUpdateTime'] ?? '',
            $data['lastUpdateTimestamp'] ?? time(),
            $data['precipitations'] ?? null,
            $data['forecasts'] ?? []
        );
    }

    /**
     * Get cache file path
     */
    private function getCacheFilePath(string $provider, string $type): string
    {
        return \KindleWeather\Config\Config::getCacheFilePath($provider, $type);
    }
}
