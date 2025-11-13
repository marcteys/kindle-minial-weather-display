<?php

namespace KindleWeather\Services;

use KindleWeather\Config\Config;

/**
 * Maps weather descriptions to icon names and characters
 */
class WeatherIconMapper
{
    private array $iconsList = [];

    public function __construct()
    {
        $this->loadIconsFromXml();
    }

    /**
     * Load weather icons from XML file
     */
    private function loadIconsFromXml(): void
    {
        if (!file_exists(Config::WEATHER_ICONS_XML)) {
            Logger::warning("Weather icons XML file not found");
            return;
        }

        $xmlContent = file_get_contents(Config::WEATHER_ICONS_XML);
        $xml = simplexml_load_string($xmlContent, "SimpleXMLElement");

        foreach ($xml->children() as $child) {
            $attributes = $child->attributes();
            $name = (string) $attributes->name;
            $character = (string) $child[0];
            $this->iconsList[$name] = $character;
        }

        Logger::debug("Loaded " . count($this->iconsList) . " weather icons from XML");
    }

    /**
     * Get icon name from weather description (French)
     */
    public function getIconName(string $weatherDescription): string
    {
        $normalized = $this->normalizeText($weatherDescription);

        // Mapping from French weather descriptions to icon names
        $mapping = [
            '' => 'day-sunny',
            'nuit claire' => 'night-clear',
            'tres nuageux' => 'cloudy',
            'couvert' => 'cloudy',
            'brume' => 'fog',
            'brume ou bancs de brouillard' => 'fog',
            'brouillard' => 'fog',
            'brouillard givrant' => 'fog',
            'risque de grele' => 'hail',
            'orages' => 'lightning',
            'risque d\'orages' => 'lightning',
            'risque dorages' => 'lightning',
            'pluie orageuses' => 'thunderstorm',
            'pluies orageuses' => 'thunderstorm',
            'averses orageuses' => 'thunderstorm',
            'ciel voile' => 'cloud',
            'ciel voile nuit' => 'night-alt-cloudy',
            'eclaircies' => 'day-cloudy',
            'peu nuageux' => 'day-sunny-overcast',
            'pluie forte' => 'rain',
            'bruine / pluie faible' => 'showers',
            'bruine' => 'showers',
            'pluie faible' => 'showers',
            'pluies eparses / rares averses' => 'showers',
            'pluies eparses' => 'showers',
            'rares averses' => 'showers',
            'pluie moderee' => 'rain',
            'pluie / averses' => 'rain',
            'averses' => 'rain',
            'pluie' => 'rain',
            'neige' => 'snow',
            'neige forte' => 'snow',
            'quelques flocons' => 'snow',
            'averses de neige' => 'snow',
            'neige / averses de neige' => 'snow',
            'pluie et neige' => 'snow',
            'pluie verglacante' => 'sleet',
            'ensoleille' => 'day-sunny',
        ];

        return $mapping[$normalized] ?? 'day-sunny';
    }

    /**
     * Get icon character for rendering
     */
    public function getIconCharacter(string $iconName, bool $isNight = false): string
    {
        // Add night prefix if needed
        if ($isNight && strpos($iconName, 'wi') !== 0) {
            $iconKey = 'wi_night_' . str_replace('-', '_', $iconName);
        } elseif (strpos($iconName, 'wi') !== 0) {
            $iconKey = 'wi_' . str_replace('-', '_', $iconName);
        } else {
            $iconKey = str_replace('-', '_', $iconName);
        }

        return $this->iconsList[$iconKey] ?? '';
    }

    /**
     * Normalize text for comparison
     */
    private function normalizeText(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Remove accents
        $text = $this->removeAccents($text);

        // Replace spaces with nothing for comparison
        $text = str_replace(' ', ' ', $text);

        return trim($text);
    }

    /**
     * Remove accents from text
     */
    private function removeAccents(string $text): string
    {
        $replacements = [
            'Ç' => 'C', 'ç' => 'c',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'o', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'Ý' => 'Y',
        ];

        foreach ($replacements as $from => $to) {
            $text = str_replace($from, $to, $text);
        }

        // Remove apostrophes
        $text = str_replace('\'', '', $text);

        return $text;
    }
}
