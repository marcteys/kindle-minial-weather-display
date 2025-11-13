<?php

namespace KindleWeather\Models;

/**
 * Weather data model representing processed weather information
 * ready for display on the Kindle screen
 */
class WeatherData
{
    public string $lastUpdateDate;
    public string $lastUpdateTime;
    public ?array $precipitations; // null if no precipitation
    public array $forecasts;
    public int $lastUpdateTimestamp;

    public function __construct(
        string $lastUpdateDate,
        string $lastUpdateTime,
        int $lastUpdateTimestamp,
        ?array $precipitations,
        array $forecasts
    ) {
        $this->lastUpdateDate = $lastUpdateDate;
        $this->lastUpdateTime = $lastUpdateTime;
        $this->lastUpdateTimestamp = $lastUpdateTimestamp;
        $this->precipitations = $precipitations;
        $this->forecasts = $forecasts;
    }

    /**
     * Get current temperature
     */
    public function getCurrentTemperature(): int
    {
        return $this->forecasts[0]['temperature'] ?? 0;
    }

    /**
     * Get current weather text
     */
    public function getCurrentWeatherText(): string
    {
        return $this->forecasts[0]['weatherText'] ?? '';
    }

    /**
     * Get current weather icon character
     */
    public function getCurrentWeatherIcon(): string
    {
        return $this->forecasts[0]['iconChar'] ?? '';
    }

    /**
     * Get min temperature for current period
     */
    public function getMinTemperature(): int
    {
        return $this->forecasts[0]['minTemperature'] ?? 0;
    }

    /**
     * Get max temperature for current period
     */
    public function getMaxTemperature(): int
    {
        return $this->forecasts[0]['maxTemperature'] ?? 0;
    }

    /**
     * Get current weather icon text (for photo folder selection)
     */
    public function getCurrentIconText(): string
    {
        return $this->forecasts[0]['iconText'] ?? 'cloud';
    }

    /**
     * Check if there are precipitations to display
     */
    public function hasPrecipitations(): bool
    {
        return $this->precipitations !== null;
    }

    /**
     * Get upcoming forecasts (excluding current)
     */
    public function getUpcomingForecasts(): array
    {
        return array_slice($this->forecasts, 1);
    }

    /**
     * Convert to array for debugging
     */
    public function toArray(): array
    {
        return [
            'lastUpdateDate' => $this->lastUpdateDate,
            'lastUpdateTime' => $this->lastUpdateTime,
            'lastUpdateTimestamp' => $this->lastUpdateTimestamp,
            'precipitations' => $this->precipitations,
            'forecasts' => $this->forecasts,
        ];
    }
}
