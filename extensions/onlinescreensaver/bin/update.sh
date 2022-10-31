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
	logger "update.sh: Could not find config.sh"
	TMPFILE=/mnt/us/extensions/onlinescreensaver/tmp.onlinescreensaver.png
fi

# load utils
if [ -e "utils.sh" ]; then
	source ./utils.sh
else
	logger "update.sh: Could not find utils.sh"
	exit
fi

# do nothing if no URL is set
if [ -z $IMAGE_URI ]; then
	logger "update.sh: No image URL has been set. Please edit config.sh."
	return
fi

# Set Powersave
logger "update.sh: Set CPU scaling governer to powersave"
echo powersave >/sys/devices/system/cpu/cpu0/cpufreq/scaling_governor

if [ 1 -eq PREVENT_SCREENSAVER ]; then
	# Prevent screensaver (?)
	logger "update.sh: Set prevent screen saver to true"
	lipc-set-prop com.lab126.powerd preventScreenSaver 1
fi

if [ 1 -eq $FORCE_SCREENSAVER ]; then
	logger "update.sh forcing screensaver"
	lipc-get-prop com.lab126.powerd status | grep "Screen Saver" 
	if [ $? -eq 1 ]
	then
		logger "update.sh simulating power button"
		powerd_test -p
		# simulate power button to go into screensaver mode
	fi
fi

# enable wireless if it is currently off
if [ 0 -eq `lipc-get-prop com.lab126.cmd wirelessEnable` ]; then
	logger "update.sh: WiFi is off, turning it on now"
	lipc-set-prop com.lab126.cmd wirelessEnable 1
	DISABLE_WIFI=1
fi

# wait for network to be up
TIMER=${NETWORK_TIMEOUT}     # number of seconds to attempt a connection
CONNECTED=0                  # whether we are currently connected
while [ 0 -eq $CONNECTED ]; do
	# test whether we can ping outside
	/bin/ping -c 1 $TEST_DOMAIN > /dev/null && CONNECTED=1
	logger "update.sh: Trying to ping"

	# if we can't, checkout timeout or sleep for 2s
	if [ 0 -eq $CONNECTED ]; then
		TIMER=$(($TIMER-1))
		if [ 0 -eq $TIMER ]; then
			logger "update.sh: No internet connection after ${NETWORK_TIMEOUT} seconds, aborting."
			break
		else
			logger "update.sh: Sleep 2s beceause we cannot connect to wifi."
			sleep 2
		fi
	fi
done

if [ 1 -eq $CONNECTED ]; then
	logger "update.sh: Wifi connected."

    CHARGING_FILE=`kdb get system/driver/charger/SYS_CHARGING_FILE`
    IS_CHARGING=$(cat $CHARGING_FILE)
    CHECKBATTERY=$(gasgauge-info -s | sed 's/.$//')
    CHECKCHARGECURRENT=$(gasgauge-info -l | sed 's/mA//g')
	logger "update.sh: Battery: isCharging=${IS_CHARGING} percentage=${CHECKBATTERY}% current=${CHECKCHARGECURRENT}mA"

	if [ 1 -eq $DO_BATTERY_QUERYSTRING ]; then
		if [ ${CHECKBATTERY} -le ${BATTERY_LOW_QUERYSTRING} ]; then
			GENERATION_URI="$GENERATION_URI?battery=$CHECKBATTERY"
		fi
	fi

	logger "update.sh: Trying to ping  $GENERATION_URI"
	wget -q $GENERATION_URI
	sleep 1 #Wait one second
	logger "update.sh: Image generated on server. Trying to get it."

	if wget -q $IMAGE_URI -O $TMPFILE > "${SCRIPTDIR}/get.log"; then
		mv $TMPFILE $SCREENSAVERFILE
		logger "update.sh: Screen saver image file updated"

		lipc-get-prop com.lab126.powerd status | grep "Screen Saver"
          #if [ $? -eq 0 ] #  $? is the result of previous call
          if [ 0 -eq 0 ] # ALWAYS TRUE /!\ dirty hack
		then
			logger "update.sh: Updating image on screen"
	        eips -f -g $SCREENSAVERFILE

	        if [ ${CHECKBATTERY} -le ${BATTERYLOW} ]; then
		   		logger "update.sh: Battery super low, below ${BATTERYLOW}"
		   		eips -f -g "${BATTERY_LOW_IMAGE}"
		   		
		   		#Display Text
		   		if [ 1 -eq $BATTERY_TEXT_DISPLAY ]; then
		   			batt=`powerd_test -s | awk -F: '/Battery Level/ {print $2}'`
	            	eips 20 1 "Batterie:$batt"
	            fi
	        else
		   		logger "update.sh: Remaining battery ${CHECKBATTERY}"
	        fi
		else
			logger "update.sh: Error updating screensaver. Probably not in sleep mode."
			if [ 1 -eq $DONOTRETRY ]; then
				touch $SCREENSAVERFILE
			fi
		fi
	fi

else 
	logger "update.sh: Skipping image display beceause we can't connect to wifi."
fi

# disable wireless if necessary
if [ 1 -eq $DISABLE_WIFI ]; then
	logger "update.sh: Disabling WiFi"
	lipc-set-prop com.lab126.cmd wirelessEnable 0
fi
