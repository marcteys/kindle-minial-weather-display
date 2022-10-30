##############################################################################
# Logs a message to a log file (or to console if argument is /dev/stdout)

logger () {
	MSG=$1
	
	# do nothing if logging is not enabled
	if [ "x1" != "x$LOGGING" ]; then
		return
	fi

	# if no logfile is specified, set a default
	if [ -z $LOGFILE ]; then
		$LOGFILE=stdout
	fi

	echo `date`: $MSG >> $LOGFILE
}


##############################################################################
# Retrieves the current time in seconds

currentTime () {
	date +%s
}


##############################################################################
# sets an RTC alarm
# arguments: $1 - time in seconds from now
# arguments: $2 - USE_RTC
# arguments: $3 - RTC

wait_for () { 
	delay=$1
	USE_RTC=0
	RTC=1
	now=$(currentTime)

        if [ "x1" == "x$LOGGING" ]; then
		state=`/usr/bin/powerd_test -s | grep "Powerd state"`
		defer=`/usr/bin/powerd_test -s | grep defer`
		remain=`/usr/bin/powerd_test -s | grep Remain`
		batt=`/usr/bin/powerd_test -s | grep Battery`
		logger "wait_for called with $delay, now=$now, $state, $defer, $remain, $batt"
	fi		
	# calculate the time we should return
	ENDWAIT=$(( $(currentTime) + $1 ))

	# wait for timeout to expire
	logger "Wait_for() $1 seconds"

	while [ $(currentTime) -lt $ENDWAIT ]; do
		REMAININGWAITTIME=$(( $ENDWAIT - $(currentTime) ))
		logger "REMAININGWAITTIME $REMAININGWAITTIME"

		if [ 0 -lt $REMAININGWAITTIME ]; then
			sleep 2
			lipc-get-prop com.lab126.powerd status | grep "Screen Saver" 
			if [ $? -eq 0 ] #  $? is the result of previous call
			then
				# in screensaver mode
				logger "Go to sleep for $REMAININGWAITTIME seconds, wlan off"
				lipc-set-prop com.lab126.cmd wirelessEnable 0
				#/mnt/us/extensions/onlinescreensaver/bin/rtcwake -d rtc$RTC -s $REMAININGWAITTIME -m mem

				if [ 1 -eq $USE_RTC ]; then
					logger "Sleep: RTC"
					/mnt/us/extensions/onlinescreensaver/bin/rtcwake -d rtc$RTC -s $REMAININGWAITTIME -m mem
		        else
		        	logger "Sleep: sleep function"
		            sleep $REMAININGWAITTIME
		        fi
				logger "woke up again"
				logger "Finished waiting, switch wireless back on"
				lipc-set-prop com.lab126.cmd wirelessEnable 1
			else
				# not in screensaver mode - don't really sleep with rtcwake
				logger "not in screensaver mode"
				sleep $REMAININGWAITTIME
			fi
		fi
	done

	logger "done"

	# not sure whether this is required
	lipc-set-prop com.lab126.powerd -i deferSuspend 40
	
}

