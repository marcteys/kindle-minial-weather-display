# Kindle Minimal Weather Display

In this repository you will find the source code to display display a minimal weather information on an old kindle 3. Tested on Kindle 4.1.1.

The code source provide the two required components: the code source to be uploaded on the kinder and the code source of the php web server. 

## Features 

* Display weather information for today and tomorrow as well as a nice image
* Display precipitation of the next hour
* ⚠Currently it is setup to work in French on Paris, but could be easiliy changed.  


## Install


### Web server setup

The web server generates an image. 

*Steps:*
1. Upload `php_server` on a remote php server 

The main server settings are located under `settings.php`. 

There are two ways to access the image.
1. Opening the url `getImage.php` will generate an image `weatherImage.png` on the root folder. 
2. Opening `getImage.png` (be sure to enable Mod_rewrite on your server)


### Kindle setup

This repo uses a modified version of [Online Screensaver](https://www.mobileread.com/forums/showthread.php?t=236104) with [this](https://www.mobileread.com/forums/showpost.php?p=3009582&postcount=36) fix.

*Steps:*
1. Jailbreak your kindle
2. Install KUAL
3. Install Kite (needed to start script automatically on boot) 
4. Copy `kindle\extensions\onlinescreensaver\` to you kindle root under `\extensions\onlinescreensaver\`
5. Copy `kindle\kite\` to you kindle root under `\kite\`

The main kindle settings are located under `kindle\extensions\onlinescreensaver\config.sh`. The main setting to change is the defaut image URL : `SERVERDIR`. 



## Known issues, upgrades and todos

- Right now, I use the french open weather API. As such, the interface is in french. It should be fairly easy to change the locale or even pass it as argument. 



## Inspiration, resources, references 


I draw inspiration from various projects. 

- https://github.com/mpetroff/kindle-weather-display
- https://www.shatteredhaven.com/2012/11/1347365-kindle-weather-display.html
- https://www.galacticstudios.org/kindle-weather-display/
- https://github.com/sibbl/hass-lovelace-kindle-4
