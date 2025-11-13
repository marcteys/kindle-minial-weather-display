<?php

namespace KindleWeather\Services;

use KindleWeather\Models\WeatherData;
use KindleWeather\Config\Config;
use Imagick;
use ImagickDraw;

/**
 * Image rendering service
 * Generates the weather image using ImageMagick
 */
class ImageRenderer
{
    private WeatherData $weatherData;
    private ?int $batteryLevel;
    private Imagick $image;

    // Color constants
    private const COLOR_WHITE = 'rgba(255, 255, 255, 1)';
    private const COLOR_WHITE_TRANSPARENT = 'rgba(255, 255, 255, 0.5)';
    private const COLOR_TRANSPARENT = 'rgba(255, 255, 255, 0)';
    private const COLOR_BLACK_TRANSPARENT = 'rgba(0, 0, 0, 0.2)';

    // Layout constants
    private const MAIN_TEMP_Y = 225;
    private const FORECAST_BASE_Y = 630;
    private const PRECIPITATION_Y = 335;

    public function __construct(WeatherData $weatherData, ?int $batteryLevel = null)
    {
        $this->weatherData = $weatherData;
        $this->batteryLevel = $batteryLevel;
    }

    /**
     * Render the complete weather image
     */
    public function render(): Imagick
    {
        Logger::info("Starting image rendering");

        // Load and prepare background
        $this->loadBackgroundImage();
        $this->applyGradients();

        // Render weather information
        $this->renderCurrentWeather();
        $this->renderDate();

        // Render battery if provided
        if ($this->batteryLevel !== null) {
            $this->renderBattery();
        }

        // Render precipitations if any
        if ($this->weatherData->hasPrecipitations()) {
            $this->renderPrecipitations();
        }

        // Render upcoming forecasts
        $this->renderUpcomingForecasts();

        Logger::info("Image rendering completed");

        return $this->image;
    }

    /**
     * Load background image based on weather condition
     */
    private function loadBackgroundImage(): void
    {
        $weatherFolder = $this->weatherData->getCurrentIconText();
        $imagesDir = Config::PHOTOS_DIR . '/' . $weatherFolder . '/';

        // Fallback to cloud folder if weather folder doesn't exist
        if (!is_dir($imagesDir)) {
            $imagesDir = Config::PHOTOS_DIR . '/' . Config::DEFAULT_PHOTO_FOLDER . '/';
            Logger::warning("Weather folder '{$weatherFolder}' not found, using default");
        }

        // Get random image from folder
        $images = glob($imagesDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

        if (empty($images)) {
            throw new \Exception("No background images found in {$imagesDir}");
        }

        $randomImage = $images[array_rand($images)];
        Logger::info("Loading background image: {$randomImage}");

        // Load and process image
        $this->image = new Imagick(realpath($randomImage));
        $this->image->setImageCompressionQuality(100);
        $this->image->cropThumbnailImage(Config::IMAGE_WIDTH, Config::IMAGE_HEIGHT);

        Logger::debug("Background image loaded and cropped");
    }

    /**
     * Apply gradient overlays to the image
     */
    private function applyGradients(): void
    {
        Logger::debug("Applying gradients");

        // Top gradient
        $topGradient = new Imagick();
        $topGradient->newPseudoImage(600, 100, 'gradient:#bbbbbb-#ffffff');
        $this->image->compositeImage($topGradient, Imagick::COMPOSITE_MULTIPLY, 0, 0);

        // Bottom gradient
        $bottomGradient = new Imagick();
        $bottomGradient->newPseudoImage(600, 280, 'gradient:#ffffff-#555555');
        $this->image->compositeImage($bottomGradient, Imagick::COMPOSITE_MULTIPLY, 0, 520);

        // Center gradient (darken if background is too bright)
        $centerSample = clone $this->image;
        $centerSample->cropImage(400, 140, 110, 220);
        $pixel = $centerSample->getImagePixelColor(1, 1);
        $colors = $pixel->getHSL();

        if ($colors['luminosity'] > 0.54) {
            $centerGradient1 = new Imagick();
            $centerGradient1->newPseudoImage(600, 100, 'gradient:#ffffff-#aaaaaa');
            $this->image->compositeImage($centerGradient1, Imagick::COMPOSITE_MULTIPLY, 0, 100);

            $centerGradient2 = new Imagick();
            $centerGradient2->newPseudoImage(600, 200, 'gradient:#aaaaaa-#ffffff');
            $this->image->compositeImage($centerGradient2, Imagick::COMPOSITE_MULTIPLY, 0, 200);
        }

        Logger::debug("Gradients applied");
    }

    /**
     * Render current weather information (main temperature, weather text, icon)
     */
    private function renderCurrentWeather(): void
    {
        $baseY = self::MAIN_TEMP_Y;

        // Main temperature
        $this->writeText(
            $this->weatherData->getCurrentTemperature() . '°',
            self::COLOR_WHITE,
            110,
            Config::FONT_EXPANDED,
            370,
            $baseY,
            Imagick::ALIGN_CENTER
        );

        // Weather description
        $this->writeText(
            $this->weatherData->getCurrentWeatherText(),
            self::COLOR_WHITE,
            40,
            Config::FONT_REGULAR,
            300,
            $baseY + 75,
            Imagick::ALIGN_CENTER
        );

        // Min temperature
        $this->writeText(
            $this->weatherData->getMinTemperature() . '°',
            self::COLOR_WHITE_TRANSPARENT,
            40,
            Config::FONT_EXPANDED,
            460,
            $baseY - 50,
            Imagick::ALIGN_LEFT
        );

        // Max temperature
        $this->writeText(
            $this->weatherData->getMaxTemperature() . '°',
            self::COLOR_WHITE,
            40,
            Config::FONT_EXPANDED,
            460,
            $baseY,
            Imagick::ALIGN_LEFT
        );

        // Weather icon
        $this->writeText(
            $this->weatherData->getCurrentWeatherIcon(),
            self::COLOR_WHITE,
            95,
            Config::FONT_WEATHER_ICONS,
            200,
            $baseY,
            Imagick::ALIGN_CENTER
        );

        Logger::debug("Current weather rendered");
    }

    /**
     * Render date and time information
     */
    private function renderDate(): void
    {
        // Date (left)
        $dateText = preg_replace('/\s{2,}/', ' ', $this->weatherData->lastUpdateDate);
        $this->writeText(
            $dateText,
            self::COLOR_WHITE,
            27,
            Config::FONT_REGULAR,
            35,
            50,
            Imagick::ALIGN_LEFT
        );

        // Last update time (right)
        $this->writeText(
            $this->weatherData->lastUpdateTime,
            self::COLOR_WHITE,
            27,
            Config::FONT_REGULAR,
            565,
            50,
            Imagick::ALIGN_RIGHT
        );

        // Current time (below last update)
        $this->writeText(
            date('H\hi'),
            self::COLOR_WHITE,
            14,
            Config::FONT_REGULAR,
            565,
            67,
            Imagick::ALIGN_RIGHT
        );

        Logger::debug("Date and time rendered");
    }

    /**
     * Render battery indicator
     */
    private function renderBattery(): void
    {
        Logger::debug("Rendering battery: {$this->batteryLevel}%");

        if ($this->batteryLevel <= Config::BATTERY_LOW_THRESHOLD) {
            // Large battery icon for low battery
            $this->renderLargeBatteryIcon();
        } else {
            // Small battery icon
            $this->renderSmallBatteryIcon();
        }
    }

    /**
     * Render large battery icon (for low battery warning)
     */
    private function renderLargeBatteryIcon(): void
    {
        $posX = 225;
        $posY = 355;

        // Battery body
        $draw = new ImagickDraw();
        $draw->setFillColor(self::COLOR_TRANSPARENT);
        $draw->setStrokeColor(self::COLOR_WHITE);
        $draw->setStrokeOpacity(1);
        $draw->setStrokeWidth(6);
        $draw->roundRectangle($posX, $posY, $posX + 150, $posY + 70, 5, 5);
        $this->image->drawImage($draw);

        // Battery tip
        $draw = new ImagickDraw();
        $draw->setFillColor(self::COLOR_TRANSPARENT);
        $draw->setStrokeColor(self::COLOR_WHITE);
        $draw->setStrokeOpacity(1);
        $draw->setStrokeWidth(6);
        $draw->roundRectangle($posX + 150, $posY + 20, $posX + 170, $posY + 50, 3, 3);
        $this->image->drawImage($draw);

        // Battery fill
        $draw = new ImagickDraw();
        $draw->setFillColor(self::COLOR_WHITE);
        $draw->setStrokeOpacity(0);
        $draw->rectangle($posX + 8, $posY + 8, $posX + 20, $posY + 62);
        $this->image->drawImage($draw);

        // Battery percentage text
        $this->writeText(
            (string) $this->batteryLevel,
            self::COLOR_WHITE,
            50,
            Config::FONT_REGULAR,
            $posX + 68,
            $posY + 52,
            Imagick::ALIGN_LEFT
        );

        Logger::debug("Large battery icon rendered");
    }

    /**
     * Render small battery icon (normal battery level)
     */
    private function renderSmallBatteryIcon(): void
    {
        $posX = 420;
        $posY = 30;

        // Battery body
        $draw = new ImagickDraw();
        $draw->setFillColor(self::COLOR_TRANSPARENT);
        $draw->setStrokeColor(self::COLOR_WHITE);
        $draw->setStrokeOpacity(1);
        $draw->setStrokeWidth(2);
        $draw->roundRectangle($posX, $posY, $posX + 60, $posY + 30, 5, 5);
        $this->image->drawImage($draw);

        // Battery tip
        $draw = new ImagickDraw();
        $draw->setFillColor(self::COLOR_TRANSPARENT);
        $draw->setStrokeColor(self::COLOR_WHITE);
        $draw->setStrokeOpacity(1);
        $draw->setStrokeWidth(2);
        $draw->rectangle($posX + 60, $posY + 10, $posX + 66, $posY + 20);
        $this->image->drawImage($draw);

        // Battery fill (proportional to battery level)
        $fillWidth = (int) (($this->batteryLevel / 100) * 50);
        $draw = new ImagickDraw();
        $draw->setFillColor(self::COLOR_WHITE);
        $draw->setStrokeWidth(0);
        $draw->rectangle($posX + 5, $posY + 5, $posX + 5 + $fillWidth + 1, $posY + 25);
        $this->image->drawImage($draw);

        // Battery percentage text
        $this->writeText(
            (string) $this->batteryLevel,
            self::COLOR_WHITE,
            24,
            Config::FONT_REGULAR,
            $posX + 26,
            $posY + 24,
            Imagick::ALIGN_LEFT
        );

        Logger::debug("Small battery icon rendered");
    }

    /**
     * Render precipitation chart
     */
    private function renderPrecipitations(): void
    {
        Logger::debug("Rendering precipitation chart");

        $precipitations = $this->weatherData->precipitations;
        $leftMargin = 70;
        $topY = self::PRECIPITATION_Y;
        $barWidth = 36;
        $barHeight = 7;
        $margin = 3;

        // Rain icon
        $this->writeText(
            '', // Rain drop icon from weather font
            self::COLOR_WHITE,
            36,
            Config::FONT_WEATHER_ICONS,
            55,
            $topY + 15,
            Imagick::ALIGN_CENTER
        );

        // First 6 bars (narrow)
        for ($i = 0; $i < 6; $i++) {
            if (!isset($precipitations[$i])) break;

            $draw = new ImagickDraw();
            $draw->setFillColor(self::COLOR_WHITE);

            $posX = $leftMargin + $i * $barWidth + ($i * $margin);

            // Draw vertical bars based on intensity
            for ($level = 0; $level < $precipitations[$i]['value']; $level++) {
                $barY = $topY - ($level * $barHeight) - ($level * $margin);
                $draw->rectangle($posX, $barY, $posX + $barWidth, $barY + $barHeight);
            }

            $this->image->drawImage($draw);
        }

        // Last 3 bars (wider)
        $wideBarWidth = 72;
        $leftMargin = 304;

        for ($i = 0; $i < 3; $i++) {
            $dataIndex = $i + 6;
            if (!isset($precipitations[$dataIndex])) break;

            $draw = new ImagickDraw();
            $draw->setFillColor(self::COLOR_WHITE);

            $posX = $leftMargin + $i * $wideBarWidth + ($i * $margin);

            // Draw vertical bars based on intensity
            for ($level = 0; $level < $precipitations[$dataIndex]['value']; $level++) {
                $barY = $topY - ($level * $barHeight) - ($level * $margin);
                $draw->rectangle($posX, $barY, $posX + $wideBarWidth, $barY + $barHeight);
            }

            $this->image->drawImage($draw);
        }

        // Time labels
        for ($i = 0; $i < 5; $i++) {
            $text = ($i + 1) . '0min';
            $textX = 150 + ($margin + $wideBarWidth) * $i;
            $this->writeText(
                $text,
                self::COLOR_WHITE,
                12,
                Config::FONT_REGULAR,
                $textX,
                $topY + 22,
                Imagick::ALIGN_CENTER
            );
        }

        // Start time
        $this->writeText(
            $precipitations[0]['time'],
            self::COLOR_WHITE,
            14,
            Config::FONT_BOLD,
            70,
            $topY + 24,
            Imagick::ALIGN_LEFT
        );

        // End time
        $endIndex = count($precipitations) - 1;
        $this->writeText(
            $precipitations[$endIndex]['time'],
            self::COLOR_WHITE,
            14,
            Config::FONT_BOLD,
            526,
            $topY + 24,
            Imagick::ALIGN_RIGHT
        );

        Logger::debug("Precipitation chart rendered");
    }

    /**
     * Render upcoming forecast periods
     */
    private function renderUpcomingForecasts(): void
    {
        Logger::debug("Rendering upcoming forecasts");

        $forecasts = $this->weatherData->getUpcomingForecasts();
        $position = 85; // Starting position + margin
        $width = 110;
        $baseY = self::FORECAST_BASE_Y;

        foreach ($forecasts as $i => $forecast) {
            // Draw separator line for new day (when moment is "Matin")
            if ($i > 0 && $forecast['moment'] === 'Matin') {
                $draw = new ImagickDraw();
                $draw->setStrokeColor(self::COLOR_WHITE_TRANSPARENT);
                $draw->setFillColor(self::COLOR_TRANSPARENT);
                $draw->setStrokeWidth(2.5);
                $draw->line(
                    $position - ($width / 2),
                    $baseY - 25,
                    $position - ($width / 2),
                    $baseY + 115
                );
                $this->image->drawImage($draw);
            }

            // Moment text (Matin, Aprèm', Soir, Nuit)
            $this->writeText(
                $forecast['moment'],
                self::COLOR_WHITE,
                27,
                Config::FONT_REGULAR,
                $position,
                $baseY,
                Imagick::ALIGN_CENTER
            );

            // Weather icon
            $this->writeText(
                $forecast['iconChar'],
                self::COLOR_WHITE,
                45,
                Config::FONT_WEATHER_ICONS,
                $position,
                $baseY + 60,
                Imagick::ALIGN_CENTER
            );

            // Temperature
            $this->writeText(
                $forecast['temperature'] . '°',
                self::COLOR_WHITE,
                27,
                Config::FONT_BOLD,
                $position,
                $baseY + 110,
                Imagick::ALIGN_CENTER
            );

            $position += $width;
        }

        Logger::debug("Upcoming forecasts rendered");
    }

    /**
     * Write text on the image
     */
    private function writeText(
        string $text,
        string $fillColor,
        int $fontSize,
        string $font,
        int $x,
        int $y,
        int $align
    ): void {
        $draw = new ImagickDraw();
        $draw->setFillColor($fillColor);
        $draw->setStrokeWidth(0);
        $draw->setFontSize($fontSize);
        $draw->setFont($font);
        $draw->setTextAlignment($align);

        $this->image->annotateImage($draw, $x, $y, 0, $text);
    }

    /**
     * Convert image to grayscale and quantize for e-ink display
     */
    public static function convertToGrayscale(Imagick $image): Imagick
    {
        Logger::info("Converting to grayscale");

        // Convert to grayscale
        $image->setImageType(Imagick::IMGTYPE_GRAYSCALE);
        $image = $image->fxImage('intensity');

        // Quantize to reduce colors for e-ink display
        $image->quantizeImage(
            Config::GRAYSCALE_COLORS,
            Config::GRAYSCALE_COLORSPACE,
            Config::GRAYSCALE_TREE_DEPTH,
            Config::GRAYSCALE_DITHER,
            false
        );

        Logger::info("Grayscale conversion completed");

        return $image;
    }
}
