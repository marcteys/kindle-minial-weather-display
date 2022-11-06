#!/bin/sh
#
##############################################################################
#
# Checks the format of the schedule configuration value
#
##############################################################################

# change to directory of this script
cd "$(dirname "$0")"

# load configuration
if [ -e "config.sh" ]; then
	source config.sh
fi

# load utils
if [ -e "utils.sh" ]; then
	source utils.sh
else
	logger "checkschedule.sh: Could not find utils.sh in `pwd`"
	exit
fi

# get minute of day
CURRENTMINUTE=$(( `date +%-H`*60 + `date +%-M` ))

# SCHEDULE="21:00-24:00=30"
for schedule in $SCHEDULE; do
	logger "checkschedule.sh: -------------------------------------------------------"
	logger "checkschedule.sh: Parsing \"$schedule\""
	read STARTHOUR STARTMINUTE ENDHOUR ENDMINUTE INTERVAL << EOF
		$( logger "checkschedule.sh:  $schedule" | sed -e 's/[:,=,\,,-]/ /g' -e 's/\([^0-9]\)0\([[:digit:]]\)/\1\2/g' )
EOF
	logger "checkschedule.sh: 	Starts at $STARTHOUR hours and $STARTMINUTE minutes"
	logger "checkschedule.sh: 	Ends at $ENDHOUR hours and $ENDMINUTE minutes"
	logger "checkschedule.sh: 	Interval is $INTERVAL minutes"

	START=$(( 60*$STARTHOUR + $STARTMINUTE ))
	END=$(( 60*$ENDHOUR + $ENDMINUTE ))

	if [ $END -lt $START ]; then
		logger "checkschedule.sh: !!!!!!! End time is before start time."
	fi

	if [ $CURRENTMINUTE -ge $START ] && [ $CURRENTMINUTE -lt $END ]; then
		logger "checkschedule.sh:     --> This is the active setting"
	fi
done
