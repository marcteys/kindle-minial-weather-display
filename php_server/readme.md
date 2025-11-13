# Kindle Weather Display - PHP Server

A modern, refactored PHP server that generates weather images optimized for Kindle e-ink displays. Built with MVC architecture and multi-provider support.

![Tested on Kindle 4.1.1](https://img.shields.io/badge/Kindle-4.1.1-green.svg)
![PHP 7.0+](https://img.shields.io/badge/PHP-7.0+-blue.svg)

## Features

- 🏗️ **Clean MVC Architecture** - Well-organized, maintainable code
- 🌦️ **Multi-Provider Support** - Switch between Météo France and OpenWeatherMap
- 🎨 **E-ink Optimized** - 16-color grayscale quantization for perfect Kindle display
- 📦 **Smart Caching** - Reduces API calls, with automatic fallback on errors
- 🔋 **Battery Display** - Shows Kindle battery level on low charge
- 🐛 **Debug Interface** - Visual dashboard for testing and troubleshooting
- 🚀 **No Composer Required** - Simple PSR-4 autoloader included

## Quick Start

### 1. Installation

Upload the entire `php_server` directory to your web server:

```bash
# Via FTP, SFTP, or direct server access
scp -r php_server/ user@your-server.com:/var/www/html/weather/
```

Ensure these permissions:
```bash
chmod 755 cache/ logs/
chmod 644 src/**/*.php
chmod 644 *.php
```

### 2. Configuration

Edit `src/Config/Config.php`:

```php
// Choose your weather provider
const WEATHER_PROVIDER = 'meteofrance'; // or 'openweathermap'

// Set your location
const LATITUDE = '47.216523';   // Your latitude
const LONGITUDE = '-1.574932';  // Your longitude

// Météo France API token (if using Météo France)
const METEOFRANCE_TOKEN = 'your_token_here';

// OpenWeatherMap API key (if using OpenWeatherMap)
const OPENWEATHERMAP_API_KEY = 'your_api_key_here';

// Cache duration (minutes between API calls)
const CACHE_DURATION_MINUTES = 10;
```

### 3. Test It

Visit the debug interface to verify everything works:

```
http://your-server.com/debug.php
```

You should see:
- ✓ Imagick extension loaded
- ✓ Weather data loaded successfully
- ✓ Generated image preview

### 4. Update Your Kindle

Edit `../kindle/extensions/onlinescreensaver/bin/config.sh` on your Kindle:

```bash
GENERATION_URI="http://your-server.com/api.php"
```

Or use the clean URL:

```bash
GENERATION_URI="http://your-server.com/weather"
```

## Usage

### API Endpoints

**Main endpoint** (generates and returns weather image):
```bash
curl "http://your-server.com/api.php" > weather.png
```

**Clean URL** (requires .htaccess):
```bash
curl "http://your-server.com/weather" > weather.png
or 
curl "http://your-server.com/weather.png" > weather.png
```

**With battery indicator**:
```bash
curl "http://your-server.com/api.php?battery=25" > weather.png
```

**Force refresh** (bypass cache):
```bash
curl "http://your-server.com/api.php?force=true" > weather.png
```

**Verbose debug output**:
```bash
curl "http://your-server.com/api.php?verbose=true"
```

**Override provider**:
```bash
curl "http://your-server.com/api.php?provider=openweathermap" > weather.png
```

### Debug Interface

The debug interface provides comprehensive diagnostics:

```
http://your-server.com/debug.php
```

Features:
- System status checks (Imagick, PHP version)
- Current configuration display
- Weather data preview
- Generated image preview
- Recent application logs
- Force refresh button

## Weather Providers

### Météo France

Default provider for French locations with detailed forecast data.

**Setup:**
1. Get API token from [Météo France](https://portail-api.meteofrance.fr/)
2. Add to `Config.php`:
   ```php
   const METEOFRANCE_TOKEN = 'your_token_here';
   const WEATHER_PROVIDER = 'meteofrance';
   ```

**Features:**
- 6-period forecast (morning, afternoon, evening, night)
- 90-minute rain intensity forecast
- French weather descriptions
- Day/night icon variations

### OpenWeatherMap

Global weather provider with worldwide coverage.

**Setup:**
1. Get API key from [OpenWeatherMap](https://openweathermap.org/api)
2. Add to `Config.php`:
   ```php
   const OPENWEATHERMAP_API_KEY = 'your_api_key_here';
   const WEATHER_PROVIDER = 'openweathermap';
   ```

**Features:**
- 5-day/3-hour forecast
- Global location support
- Multiple language support
- Standardized weather codes

## Customization

### Change Update Frequency

**Server-side** (API cache duration):
```php
// src/Config/Config.php
const CACHE_DURATION_MINUTES = 15; // Check API every 15 minutes
```

**Kindle-side** (update schedule):
```bash
# ../kindle/extensions/onlinescreensaver/bin/config.sh
SCHEDULE="00:00-07:00=240 07:00-10:00=30 10:00-24:00=90"
# Format: "START_TIME-END_TIME=INTERVAL_IN_MINUTES"
```

### Change Image Size

For different Kindle models:

```php
// src/Config/Config.php
const IMAGE_WIDTH = 758;   // Kindle Paperwhite
const IMAGE_HEIGHT = 1024;
```

### Add Custom Background Images

Add images to `Photos/{weather-condition}/`:

```
Photos/
├── cloud/       - Cloudy conditions
├── cloudy/      - Overcast
├── rain/        - Rainy weather
├── snow/        - Snowy conditions
├── fog/         - Foggy weather
└── ...          - Add more conditions
```

Images should be:
- Format: JPG, JPEG, PNG, or GIF
- Size: Larger than 600x800px (will be cropped)
- One image per file (random selection)

## Troubleshooting

### Image Not Generating

**Check debug.php:**
```
http://your-server.com/debug.php
```

Look for:
- ✗ Imagick extension not loaded → Install PHP Imagick
- ✗ Weather data error → Check API credentials
- ✗ No background images → Add images to Photos/

**Check logs:**
```bash
tail -f logs/app.log
```

### Weather Data Not Loading

**Test API directly:**
```bash
curl "http://your-server.com/api.php?force=true&verbose=true"
```

**Common issues:**
- Invalid API credentials → Verify token/key in Config.php
- Cache directory not writable → `chmod 755 cache/`
- API timeout → Increase timeout in Config.php

### Kindle Not Updating

**Check Kindle logs:**
```bash
# On Kindle
cat /mnt/us/extensions/onlinescreensaver/onlinescreensaver.log
```

**Common issues:**
- Wrong URL in config.sh → Verify GENERATION_URI
- Network timeout → Increase NETWORK_TIMEOUT in Kindle config
- Image format issue → Test URL in browser first

### Wrong Provider Being Used

**Check current provider:**
```bash
curl "http://your-server.com/debug.php" | grep "Provider:"
```

**Override for testing:**
```bash
curl "http://your-server.com/api.php?provider=meteofrance" > test.png
```

## Requirements

### Server Requirements

- **PHP**: 7.0 or higher
- **Extensions**:
  - Imagick (required)
  - intl (required for date formatting)
  - json (usually built-in)
- **ImageMagick**: Installed on server
- **Apache**: With mod_rewrite enabled (for clean URLs)
- **Permissions**: Write access to `cache/` and `logs/` directories

### Kindle Requirements

- Jailbroken Kindle (tested on 4.1.1)
- KUAL (Kindle Unified Application Launcher)
- Kite (for auto-start on boot)
- Online Screensaver extension

## File Structure

```
php_server/
├── src/                     # Source code (MVC)
│   ├── Config/              # Configuration
│   ├── Controllers/         # Request handlers
│   ├── Models/              # Data models
│   ├── Providers/           # Weather API providers
│   └── Services/            # Business logic
├── cache/                   # API cache (auto-created)
├── logs/                    # Application logs (auto-created)
├── fonts/                   # Font files
├── Photos/                  # Background images
├── api.php                  # Main API endpoint
├── debug.php                # Debug interface
├── autoload.php             # PSR-4 autoloader
└── .htaccess                # URL rewriting rules
```

## API Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `battery` | integer | Battery level (0-100) | `?battery=25` |
| `force` | boolean | Force API refresh | `?force=true` |
| `verbose` | boolean | Show debug output | `?verbose=true` |
| `export` | boolean | Save only, don't output | `?export=true` |
| `provider` | string | Override provider | `?provider=openweathermap` |

## Performance

- **First request**: ~2-3 seconds (API call + image generation)
- **Cached request**: ~0.5-1 second (image generation only)
- **Cache duration**: Configurable (default 10 minutes)
- **Image size**: ~350KB PNG (optimized for e-ink)

## Security Notes

- Keep API credentials in `Config.php` secure
- Don't commit API keys to public repositories
- Use `.gitignore` to exclude sensitive files
- Consider using environment variables for production

## Documentation

For detailed architecture and development information, see:
- [CLAUDE.md](CLAUDE.md) - Complete architecture documentation
- [src/Config/Config.php](src/Config/Config.php) - All configuration options

## Credits & Resources

**Original inspiration:**
- [kindle-weather-display](https://github.com/mpetroff/kindle-weather-display) by Matthew Petroff
- [hass-lovelace-kindle-4](https://github.com/sibbl/hass-lovelace-kindle-4) by sibbl

**Tutorials:**
- [Kindle Weather Display Tutorial](https://www.shatteredhaven.com/2012/11/1347365-kindle-weather-display.html)
- [K4NT Process Guide](https://www.galacticstudios.org/kindle-weather-display/)
- [MobileRead Forums](https://www.mobileread.com/forums/showthread.php?t=236104)

**Online Screensaver Extension:**
- [Online Screensaver](https://www.mobileread.com/forums/showthread.php?t=236104)
- [Forum Discussion](https://www.mobileread.com/forums/showthread.php?t=168270)

## License

This project builds upon open-source work from the Kindle developer community. Please respect the original authors' licenses.

## Support

For issues, questions, or contributions:
1. Check [CLAUDE.md](CLAUDE.md) for detailed documentation
2. Use the debug interface for diagnostics
3. Review logs in `logs/app.log`
4. Test with `?verbose=true` for detailed output
