#!/bin/sh

cd "$(dirname "$0")"

rm weather-image.png
eips -c
eips -c

if wget http://marcteyssier.com/experiment/epaperWeatherApi/weather-image.png; then
	eips -g weather-image.png
else
	eips -g weather-image-error.png
fi
