<?php

namespace KindleWeather\Providers;

use KindleWeather\Models\WeatherData;
use KindleWeather\Config\Config;
use KindleWeather\Services\Logger;
use KindleWeather\Services\WeatherIconMapper;

/**
 * OpenWeatherMap weather provider
 * Fetches data from OpenWeatherMap API
 */
class OpenWeatherMapProvider implements WeatherProvider
{
    private const API_BASE_URL = 'https://api.openweathermap.org/data/2.5';
    private const FORECAST_ENDPOINT = '/forecast';
    private const CURRENT_ENDPOINT = '/weather';

    private string $apiKey;
    private WeatherIconMapper $iconMapper;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->iconMapper = new WeatherIconMapper();
    }

    public function getName(): string
    {
        return 'openweathermap';
    }

    /**
     * Fetch weather data from OpenWeatherMap API
     */
    public function fetchWeatherData(float $latitude, float $longitude): WeatherData
    {
        Logger::info("Fetching weather data from OpenWeatherMap API");

        // Fetch current weather and forecast
        $currentData = $this->fetchCurrent($latitude, $longitude);
        $forecastData = $this->fetchForecast($latitude, $longitude);

        // Process and transform data
        return $this->transformData($currentData, $forecastData);
    }

    /**
     * Get weather data with caching
     */
    public function getWeatherData(float $latitude, float $longitude, bool $forceRefresh = false): WeatherData
    {
        $cacheFile = Config::getCacheFilePath($this->getName(), 'weather');
        $lastUpdateFile = Config::getCacheFilePath($this->getName(), 'last_update');

        // Check if we should use cache
        if (!$forceRefresh && file_exists($cacheFile) && file_exists($lastUpdateFile)) {
            $lastUpdate = (int) file_get_contents($lastUpdateFile);
            $timeSinceUpdate = time() - $lastUpdate;
            $cacheMaxAge = Config::CACHE_DURATION_MINUTES * 60;

            if ($timeSinceUpdate < $cacheMaxAge) {
                Logger::info("Using cached weather data (age: {$timeSinceUpdate}s)");
                $cachedData = json_decode(file_get_contents($cacheFile), true);
                return $this->weatherDataFromArray($cachedData);
            }
        }

        // Fetch fresh data
        try {
            Logger::info("Fetching fresh weather data from API");
            $weatherData = $this->fetchWeatherData($latitude, $longitude);

            // Save to cache
            file_put_contents($cacheFile, json_encode($weatherData->toArray()));
            file_put_contents($lastUpdateFile, time());

            return $weatherData;
        } catch (\Exception $e) {
            Logger::error("Failed to fetch weather data: " . $e->getMessage());

            // Try to use stale cache as fallback
            if (file_exists($cacheFile)) {
                Logger::warning("Using stale cached data as fallback");
                $cachedData = json_decode(file_get_contents($cacheFile), true);
                return $this->weatherDataFromArray($cachedData);
            }

            throw $e;
        }
    }

    /**
     * Fetch current weather from API
     */
    private function fetchCurrent(float $latitude, float $longitude): array
    {
        $url = self::API_BASE_URL . self::CURRENT_ENDPOINT
            . "?lat={$latitude}&lon={$longitude}"
            . "&appid={$this->apiKey}"
            . "&units=metric"
            . "&lang=fr";

        Logger::debug("Current weather URL: {$url}");

        $data = $this->makeApiRequest($url);

        if (!$data) {
            throw new \Exception("Failed to fetch current weather from OpenWeatherMap");
        }

        return $data;
    }

    /**
     * Fetch forecast data from API
     */
    private function fetchForecast(float $latitude, float $longitude): array
    {
        $url = self::API_BASE_URL . self::FORECAST_ENDPOINT
            . "?lat={$latitude}&lon={$longitude}"
            . "&appid={$this->apiKey}"
            . "&units=metric"
            . "&lang=fr"
            . "&cnt=40"; // Get 5 days of forecasts (8 per day, 3-hour intervals)

        Logger::debug("Forecast URL: {$url}");

        $data = $this->makeApiRequest($url);

        if (!$data) {
            throw new \Exception("Failed to fetch forecast from OpenWeatherMap");
        }

        return $data;
    }

    /**
     * Make HTTP request to API with timeout
     */
    private function makeApiRequest(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => Config::API_TIMEOUT_SECONDS,
            ],
            'socket' => [
                'connect_timeout' => Config::API_TIMEOUT_SECONDS,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Transform raw API data into WeatherData model
     */
    private function transformData(array $currentData, array $forecastData): WeatherData
    {
        $now = time();
        $dateFormatter = new \IntlDateFormatter(
            Config::LOCALE,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT
        );
        $dateFormatter->setPattern('EEEE d MMM');

        // OpenWeatherMap doesn't provide minute-by-minute precipitation
        // We'll show hourly precipitation from forecast instead
        $precipitations = $this->processPrecipitations($forecastData);

        // Process forecasts
        $forecasts = $this->processForecasts($currentData, $forecastData);

        return new WeatherData(
            lastUpdateDate: $dateFormatter->format(new \DateTime()),
            lastUpdateTime: date('H\hi', $now),
            lastUpdateTimestamp: $now,
            precipitations: $precipitations,
            forecasts: $forecasts
        );
    }

    /**
     * Process precipitation data from forecast
     * OpenWeatherMap provides rain volume, we convert to intensity levels (0-4)
     */
    private function processPrecipitations(array $forecastData): ?array
    {
        if (!isset($forecastData['list'])) {
            return null;
        }

        $precipitations = [];
        $totalIntensity = 0;
        $count = min(3, count($forecastData['list'])); // Next 9 hours (3 periods of 3h)

        for ($i = 0; $i < $count; $i++) {
            $forecast = $forecastData['list'][$i];
            $timestamp = $forecast['dt'];

            // Get rain volume (mm) in the period
            $rainVolume = $forecast['rain']['3h'] ?? 0;

            // Convert rain volume to intensity level (0-4 scale)
            // 0: no rain, 1: <1mm, 2: 1-5mm, 3: 5-10mm, 4: >10mm
            $intensity = 0;
            if ($rainVolume > 0) {
                if ($rainVolume < 1) $intensity = 1;
                elseif ($rainVolume < 5) $intensity = 2;
                elseif ($rainVolume < 10) $intensity = 3;
                else $intensity = 4;
            }

            // Split the 3-hour period into 3 one-hour segments
            for ($j = 0; $j < 3; $j++) {
                $hourTimestamp = $timestamp + ($j * 3600);
                $precipitations[] = [
                    'time' => date('H\hi', $hourTimestamp),
                    'value' => $intensity,
                ];

                $totalIntensity += $intensity;

                if (count($precipitations) >= Config::PRECIPITATION_FORECAST_COUNT) {
                    break 2;
                }
            }
        }

        // Only return precipitations if there's actual rain
        return ($totalIntensity > 0) ? $precipitations : null;
    }

    /**
     * Process forecast data to create periods similar to Météo France
     */
    private function processForecasts(array $currentData, array $forecastData): array
    {
        if (!isset($forecastData['list'])) {
            return [];
        }

        $forecasts = [];

        // First forecast is current weather
        $forecasts[] = $this->createForecastPeriod(
            $currentData,
            $this->getCurrentMomentText()
        );

        // Group forecasts by time of day
        $periods = $this->groupForecastsByPeriod($forecastData['list']);

        // Take next 5 periods
        $count = 0;
        foreach ($periods as $period) {
            if ($count >= 5) break;
            $forecasts[] = $period;
            $count++;
        }

        return $forecasts;
    }

    /**
     * Group forecasts into periods (morning, afternoon, evening, night)
     */
    private function groupForecastsByPeriod(array $forecastList): array
    {
        $periods = [];
        $currentDay = date('Y-m-d');

        foreach ($forecastList as $forecast) {
            $timestamp = $forecast['dt'];
            $hour = (int) date('H', $timestamp);
            $day = date('Y-m-d', $timestamp);

            // Skip past periods
            if ($timestamp < time()) {
                continue;
            }

            // Determine period
            $momentText = $this->getMomentText($hour);
            $periodKey = $day . '_' . $momentText;

            // Only keep one forecast per period (take the middle one)
            if (!isset($periods[$periodKey])) {
                $periods[$periodKey] = $this->createForecastPeriod($forecast, $momentText);
            }
        }

        return array_values($periods);
    }

    /**
     * Create forecast period from forecast data
     */
    private function createForecastPeriod(array $forecast, string $momentText): array
    {
        $temp = round($forecast['main']['temp']);
        $minTemp = round($forecast['main']['temp_min']);
        $maxTemp = round($forecast['main']['temp_max']);
        $weatherDescription = $forecast['weather'][0]['description'] ?? '';
        $weatherId = $forecast['weather'][0]['id'] ?? 800;

        // Map OpenWeatherMap icon to our icon system
        $iconText = $this->mapOpenWeatherIconToLocal($weatherId, $weatherDescription);
        $isNight = $this->isNightTime($forecast['dt']);

        return [
            'temperature' => $temp,
            'minTemperature' => $minTemp,
            'maxTemperature' => $maxTemp,
            'iconText' => $iconText,
            'iconChar' => $this->iconMapper->getIconCharacter($iconText, $isNight),
            'weatherText' => ucfirst($weatherDescription),
            'moment' => $momentText,
        ];
    }

    /**
     * Map OpenWeatherMap weather ID to local icon name
     */
    private function mapOpenWeatherIconToLocal(int $weatherId, string $description): string
    {
        // OpenWeatherMap weather condition codes
        // https://openweathermap.org/weather-conditions
        if ($weatherId >= 200 && $weatherId < 300) {
            return 'thunderstorm'; // Thunderstorm
        } elseif ($weatherId >= 300 && $weatherId < 400) {
            return 'showers'; // Drizzle
        } elseif ($weatherId >= 500 && $weatherId < 600) {
            return ($weatherId >= 520) ? 'rain' : 'showers'; // Rain
        } elseif ($weatherId >= 600 && $weatherId < 700) {
            return 'snow'; // Snow
        } elseif ($weatherId >= 700 && $weatherId < 800) {
            return 'fog'; // Atmosphere (fog, mist, etc.)
        } elseif ($weatherId === 800) {
            return 'day-sunny'; // Clear
        } elseif ($weatherId === 801 || $weatherId === 802) {
            return 'day-cloudy'; // Few clouds / scattered clouds
        } elseif ($weatherId === 803) {
            return 'cloudy'; // Broken clouds
        } elseif ($weatherId === 804) {
            return 'cloudy'; // Overcast clouds
        }

        return 'day-sunny';
    }

    /**
     * Get moment text for current time
     */
    private function getCurrentMomentText(): string
    {
        $hour = (int) date('H');
        return $this->getMomentText($hour);
    }

    /**
     * Get moment text based on hour
     */
    private function getMomentText(int $hour): string
    {
        if ($hour >= 6 && $hour < 12) {
            return 'Matin';
        } elseif ($hour >= 12 && $hour < 18) {
            return "Aprèm'";
        } elseif ($hour >= 18 && $hour < 22) {
            return 'Soir';
        } else {
            return 'Nuit';
        }
    }

    /**
     * Check if timestamp is during night time
     */
    private function isNightTime(int $timestamp): bool
    {
        $hour = (int) date('H', $timestamp);
        return $hour < 6 || $hour >= 22;
    }

    /**
     * Reconstruct WeatherData from cached array
     */
    private function weatherDataFromArray(array $data): WeatherData
    {
        return new WeatherData(
            lastUpdateDate: $data['lastUpdateDate'],
            lastUpdateTime: $data['lastUpdateTime'],
            lastUpdateTimestamp: $data['lastUpdateTimestamp'],
            precipitations: $data['precipitations'],
            forecasts: $data['forecasts']
        );
    }
}
