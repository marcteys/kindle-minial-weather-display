<?php

namespace KindleWeather\Services;

use KindleWeather\Config\Config;
use Imagick;
use ImagickDraw;

/**
 * Error image renderer
 * Creates error images when something goes wrong
 */
class ErrorImageRenderer
{
    private const BACKGROUND_COLOR = '#FFFFFF';
    private const ERROR_COLOR = '#000000';
    private const TITLE_COLOR = '#CC0000';
    private const BOX_COLOR = '#F0F0F0';
    private const BORDER_COLOR = '#CC0000';

    /**
     * Generate an error image with the error message
     *
     * @param \Exception $exception The exception that occurred
     * @param bool $verbose Whether to show full stack trace
     * @return Imagick The error image
     */
    public static function generateErrorImage(\Exception $exception, bool $verbose = false): Imagick
    {
        $width = Config::IMAGE_WIDTH;
        $height = Config::IMAGE_HEIGHT;

        // Create blank white image
        $image = new Imagick();
        $image->newImage($width, $height, self::BACKGROUND_COLOR);
        $image->setImageFormat(Config::IMAGE_FORMAT);

        // Draw error header
        self::drawErrorHeader($image);

        // Draw error message
        self::drawErrorMessage($image, $exception);

        // Draw error details
        self::drawErrorDetails($image, $exception, $verbose);

        // Draw footer
        self::drawFooter($image);

        // Convert to grayscale for e-ink
        return ImageRenderer::convertToGrayscale($image);
    }

    /**
     * Draw error header
     */
    private static function drawErrorHeader(Imagick $image): void
    {
        $draw = new ImagickDraw();

        // Draw border rectangle
        $draw->setStrokeColor(self::BORDER_COLOR);
        $draw->setStrokeWidth(5);
        $draw->setFillColor('none');
        $draw->rectangle(10, 10, Config::IMAGE_WIDTH - 10, Config::IMAGE_HEIGHT - 10);
        $image->drawImage($draw);

        // Draw title box
        $draw = new ImagickDraw();
        $draw->setFillColor(self::TITLE_COLOR);
        $draw->rectangle(30, 30, Config::IMAGE_WIDTH - 30, 100);
        $image->drawImage($draw);

        // Draw title text
        $draw = new ImagickDraw();
        $draw->setFillColor('#FFFFFF');
        $draw->setFont(Config::FONT_BOLD);
        $draw->setFontSize(32);
        $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
        $image->annotateImage($draw, Config::IMAGE_WIDTH / 2, 75, 0, '⚠ ERROR');

        Logger::debug("Error header drawn");
    }

    /**
     * Draw error message
     */
    private static function drawErrorMessage(Imagick $image, \Exception $exception): void
    {
        $message = $exception->getMessage();

        // Wrap text if too long
        $wrappedMessage = self::wordWrap($message, 45);

        $draw = new ImagickDraw();
        $draw->setFillColor(self::ERROR_COLOR);
        $draw->setFont(Config::FONT_BOLD);
        $draw->setFontSize(24);
        $draw->setTextAlignment(\Imagick::ALIGN_CENTER);

        $y = 150;
        foreach ($wrappedMessage as $line) {
            $image->annotateImage($draw, Config::IMAGE_WIDTH / 2, $y, 0, $line);
            $y += 30;
        }

        Logger::debug("Error message drawn");
    }

    /**
     * Draw error details
     */
    private static function drawErrorDetails(Imagick $image, \Exception $exception, bool $verbose): void
    {
        $y = 250;

        // Draw details box
        $draw = new ImagickDraw();
        $draw->setFillColor(self::BOX_COLOR);
        $draw->rectangle(30, $y, Config::IMAGE_WIDTH - 30, $y + 200);
        $image->drawImage($draw);

        // Draw details text
        $draw = new ImagickDraw();
        $draw->setFillColor(self::ERROR_COLOR);
        $draw->setFont(Config::FONT_REGULAR);
        $draw->setFontSize(16);
        $draw->setTextAlignment(\Imagick::ALIGN_LEFT);

        $y += 30;

        // Error type
        $errorType = get_class($exception);
        $image->annotateImage($draw, 50, $y, 0, "Type: " . basename(str_replace('\\', '/', $errorType)));
        $y += 25;

        // File and line
        $file = basename($exception->getFile());
        $line = $exception->getLine();
        $image->annotateImage($draw, 50, $y, 0, "File: {$file}");
        $y += 25;
        $image->annotateImage($draw, 50, $y, 0, "Line: {$line}");
        $y += 25;

        // Timestamp
        $timestamp = date('Y-m-d H:i:s');
        $image->annotateImage($draw, 50, $y, 0, "Time: {$timestamp}");

        // If verbose, add stack trace
        if ($verbose) {
            $y += 40;
            $draw->setFontSize(12);
            $image->annotateImage($draw, 50, $y, 0, "Stack trace:");
            $y += 20;

            $trace = explode("\n", $exception->getTraceAsString());
            $maxLines = min(15, count($trace));

            for ($i = 0; $i < $maxLines; $i++) {
                $line = self::truncate($trace[$i], 65);
                $image->annotateImage($draw, 50, $y, 0, $line);
                $y += 18;

                if ($y > Config::IMAGE_HEIGHT - 100) {
                    break;
                }
            }
        }

        Logger::debug("Error details drawn");
    }

    /**
     * Draw footer with help text
     */
    private static function drawFooter(Imagick $image): void
    {
        $draw = new ImagickDraw();
        $draw->setFillColor(self::ERROR_COLOR);
        $draw->setFont(Config::FONT_REGULAR);
        $draw->setFontSize(14);
        $draw->setTextAlignment(\Imagick::ALIGN_CENTER);

        $y = Config::IMAGE_HEIGHT - 60;

        $image->annotateImage($draw, Config::IMAGE_WIDTH / 2, $y, 0, "Check server logs for more details:");
        $y += 20;
        $image->annotateImage($draw, Config::IMAGE_WIDTH / 2, $y, 0, "logs/app.log");
        $y += 20;
        $image->annotateImage($draw, Config::IMAGE_WIDTH / 2, $y, 0, "Or visit: /debug.php?verbose=true");

        Logger::debug("Footer drawn");
    }

    /**
     * Word wrap text to fit within width
     */
    private static function wordWrap(string $text, int $width): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;

            if (strlen($testLine) > $width) {
                if ($currentLine !== '') {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    /**
     * Truncate text to specified length
     */
    private static function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length - 3) . '...';
    }
}
