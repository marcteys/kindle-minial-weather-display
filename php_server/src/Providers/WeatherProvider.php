<?php

namespace KindleWeather\Providers;

use KindleWeather\Models\WeatherData;

/**
 * Interface for weather data providers
 * Implementations should fetch and transform weather data from various APIs
 */
interface WeatherProvider
{
    /**
     * Fetch fresh weather data from the API
     *
     * @param float $latitude
     * @param float $longitude
     * @return WeatherData
     * @throws \Exception if API request fails
     */
    public function fetchWeatherData(float $latitude, float $longitude): WeatherData;

    /**
     * Get weather data with caching support
     *
     * @param float $latitude
     * @param float $longitude
     * @param bool $forceRefresh Force API call, bypass cache
     * @return WeatherData
     */
    public function getWeatherData(float $latitude, float $longitude, bool $forceRefresh = false): WeatherData;

    /**
     * Get the name of this provider
     */
    public function getName(): string;
}
