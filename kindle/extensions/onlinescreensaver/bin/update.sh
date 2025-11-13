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
fi

#TMPFILE=/mnt/us/extensions/onlinescreensaver/tmp.onlinescreensaver.png


# load utils
if [ -e "utils.sh" ]; then
	source ./utils.sh
else
	logger "update.sh: Could not find utils.sh"
	exit
fi

# do nothing if no URL is set
if [ -z $GENERATION_URI ]; then
	logger "update.sh: No image URL has been set. Please edit config.sh."
	return
fi

# Set Powersave
logger "update.sh: Set CPU scaling governer to powersave"
echo powersave >/sys/devices/system/cpu/cpu0/cpufreq/scaling_governor

if [ 1 -eq $PREVENT_SCREENSAVER ]; then
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
		#powerd_test -p
	#	lipc-set-prop com.lab126.powerd -i abortSuspend 1
		lipc-set-prop com.lab126.powerd -i touchScreenSaverTimeout 1
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
			logger "update.sh: Sleep 2s beceause we cannot connect to wifi. Timer : ${TIMER}"
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

#	wget -q "$GENERATION_URI"
#	wget -q "$GENERATION_URI"
#	sleep 5 #Wait one second
#	logger "update.sh: Image generated on server. Trying to get it."

	if [ -f $TMPFILE ]; then
		logger "update.sh: Remove tmpImage that was here"
  		rm $TMPFILE
	fi

	if [ -f $TMPFILE ]; then
		logger "update.sh: Remove weatherimage"
	fi

	#logger "update.sh: Trying to remove all files"
	#rm $SCREENSAVERFILE
	#rm $TMPFILE
	#logger "update.sh: Forced removing all files"

	#logger "update.sh: Trying to ping  $GENERATION_URI"
	#https://stackoverflow.com/questions/2717303/check-wgets-return-value
	#wget -q "$IMAGE_URI" >> "${SCRIPTDIR}/get.log" 2>&1
	#logger "update.sh: Test wget"

	#logger "version $(wget --version)"
	#wget man > "${SCRIPTDIR}/getlog.log" 2>&1;
	#curl --help > "${SCRIPTDIR}/curl.log" 2>&1;

	#ls /etc/ > "${SCRIPTDIR}/etc.log" 2>&1;

	# THis wget version is used : https://coral.googlesource.com/busybox/+/refs/heads/release-chef/networking/wget.c 

	#// TO TEST OR RE%OVE 
	# to test 1 : --no-check-certificate
	# to test 2 :     PID=`ps xa | grep "/bin/sh /mnt/base-us/extensions/onlinescreensaver/bin/scheduler.sh" | awk '{ print $1 }'`
	#to test 3 https://stackoverflow.com/questions/44927544/returns-the-exact-returned-value-if-wget-fails
	# to test 4 https://stackoverflow.com/questions/2717303/check-wgets-return-value
	# to test 5 https://stackoverflow.com/questions/27334926/bash-download-files-from-file-list
	# to test 6 https://github.com/coder/sshcode/issues/102#issuecomment-761688048

	logger "update.sh: Trying to get $GENERATION_URI"

	#timeout 20
	#wget "$GENERATION_URI" -O "/mnt/us/extensions/onlinescreensaver/tmp.onlinescreensaver2.png" > "${SCRIPTDIR}/get.log" 2>&1;

	#wget "$GENERATION_URI" -O "$TMPFILE" > "${SCRIPTDIR}/get.log" 2>&1;

	TIMEOUT=8
	# Timeout !
	( wget "$GENERATION_URI" -O "$TMPFILE" ) & pid=$!
	( sleep $TIMEOUT && kill -HUP $pid ) 2>/dev/null & watcher=$!
	if wait $pid 2>/dev/null; then
		logger "update.sh: your_command finished"
		pkill -HUP -P $watcher
		wait $watcher
	else
		logger "update.sh:  your_command interrupted after $TIMEOUT seconds"
		pkill -HUP -P $watcher
	fi
	#sleep 5 #Wait 5 second
	logger "update.sh: Wget has finished"

	if [ -f $TMPFILE ]; then
	#if wget -q "$IMAGE_URI" -O "$TMPFILE" > "${SCRIPTDIR}/get.log" 2>&1; then
		mv $TMPFILE $SCREENSAVERFILE
		logger "update.sh: Screen saver image file updated"

		#lipc-get-prop com.lab126.powerd status | grep "Screen Saver"
        if [ 0 -eq 0 ] # ALWAYS TRUE /!\ dirty hack
		#or
		#if [ `lipc-get-prop com.lab126.powerd status | grep "Ready" | wc -l` -gt 0 ] || [ `lipc-get-prop com.lab126.powerd status | grep "Screen Saver" | wc -l` -gt 0 ]
		then
			logger "update.sh: Updating image on screen"
	        eips -f -g $SCREENSAVERFILE
		   	logger "update.sh: Remaining battery ${CHECKBATTERY}"
		else
			logger "update.sh: Error updating screensaver. Probably not in sleep mode."
			if [ 1 -eq $DONOTRETRY ]; then
				touch $SCREENSAVERFILE
			fi
		fi
	else 
		logger "update.sh: Error no temp file."
	fi
else 
	logger "update.sh: Skipping image display beceause we can't connect to wifi."
fi

# disable wireless if necessary
if [ 1 -eq $DISABLE_WIFI ]; then
	logger "update.sh: Disabling WiFi"
	lipc-set-prop com.lab126.cmd wirelessEnable 0
fi
#logger "update.sh: Finished update.sh"
