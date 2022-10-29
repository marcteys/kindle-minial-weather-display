#!/bin/sh
#
##############################################################################
#
# Fetch weather screensaver from a configurable URL.

# change to directory of this script
cd "$(dirname "$0")"

# load configuration
if [ -e "config.sh" ]; then
	source ./config.sh
else
	logger "Could not find config.sh"
	TMPFILE=/mnt/us/extensions/onlinescreensaver/tmp.onlinescreensaver.png
fi

# load utils
if [ -e "utils.sh" ]; then
	source ./utils.sh
else
	logger "Could not find utils.sh"
	exit
fi

# do nothing if no URL is set
if [ -z $IMAGE_URI ]; then
	logger "No image URL has been set. Please edit config.sh."
	return
fi

# Set Powersave
logger "Set CPU scaling governer to powersave"
echo powersave >/sys/devices/system/cpu/cpu0/cpufreq/scaling_governor

# Prevent screensaver (?)
#logger "Set prevent screen saver to true"
#lipc-set-prop com.lab126.powerd preventScreenSaver 1

if [ 1 -eq $FORCE_SCREENSAVER ]; then
	lipc-get-prop com.lab126.powerd status | grep "Screen Saver" 
	if [ $? -eq 1 ]
	then
		powerd_test -p
		# simulate power button to go into screensaver mode
	fi
fi

# enable wireless if it is currently off
if [ 0 -eq `lipc-get-prop com.lab126.cmd wirelessEnable` ]; then
	logger "WiFi is off, turning it on now"
	lipc-set-prop com.lab126.cmd wirelessEnable 1
	DISABLE_WIFI=1
fi

# wait for network to be up
TIMER=${NETWORK_TIMEOUT}     # number of seconds to attempt a connection
CONNECTED=0                  # whether we are currently connected
while [ 0 -eq $CONNECTED ]; do
	# test whether we can ping outside
	/bin/ping -c 1 $TEST_DOMAIN > /dev/null && CONNECTED=1

	# if we can't, checkout timeout or sleep for 1s
	if [ 0 -eq $CONNECTED ]; then
		TIMER=$(($TIMER-1))
		if [ 0 -eq $TIMER ]; then
			logger "No internet connection after ${NETWORK_TIMEOUT} seconds, aborting."
			break
		else
			sleep 1
		fi
	fi
done

if [ 1 -eq $CONNECTED ]; then
	if wget -q $IMAGE_URI -O $TMPFILE; then
		mv $TMPFILE $SCREENSAVERFILE
		logger "Screen saver image file updated"
                # refresh screen
                lipc-get-prop com.lab126.powerd status | grep "Screen Saver" && (
                     logger "Updating image on screen"
                     eips -f -g $SCREENSAVERFILE

                     CHARGING_FILE=`kdb get system/driver/charger/SYS_CHARGING_FILE`
                     IS_CHARGING=$(cat $CHARGING_FILE)
                     CHECKBATTERY=$(gasgauge-info -s | sed 's/.$//')
                     CHECKCHARGECURRENT=$(gasgauge-info -l | sed 's/mA//g')
    
                     logger "Battery: isCharging=${IS_CHARGING} percentage=${CHECKBATTERY}% current=${CHECKCHARGECURRENT}mA"
                     if [ ${CHECKBATTERY} -le ${BATTERYLOW} ]; then
			      	logger "Battery below ${BATTERYLOW}"
			      	eips -f -g "${BATTERY_LOW_IMAGE}"
			      	
			      	#Display Text
			      	if [ 1 -eq $BATTERY_TEXT_DISPLAY ]; then
			      	 	batt=`powerd_test -s | awk -F: '/Battery Level/ {print $2}'`
                          	eips 20 1 "Batterie:$batt"
                          fi
                     else
			      	logger "Remaining battery ${CHECKBATTERY}"
                     fi
                )
	 
		logger "Error updating screensaver"
		if [ 1 -eq $DONOTRETRY ]; then
			touch $SCREENSAVERFILE
		fi
	fi
fi

# disable wireless if necessary
if [ 1 -eq $DISABLE_WIFI ]; then
	logger "Disabling WiFi"
	lipc-set-prop com.lab126.cmd wirelessEnable 0
fi
