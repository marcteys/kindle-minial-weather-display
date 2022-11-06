#############################################################################
### ONLINE-SCREENSAVER CONFIGURATION SETTINGS
### version modified to work on kindle 4.1.1
#############################################################################

# Local kindle folder
SCRIPTDIR="/mnt/us/extensions/onlinescreensaver"

# Remote server url. /!\ https is not supported by wget
SERVERDIR="http://marcteyssier.com/experiment/epaperWeatherApi"

# Interval in MINUTES in which to update the screensaver by default. This
# setting will only be used if no schedule (see below) fits. Note that if the
# update fails, the script is not updating again until INTERVAL minutes have
# passed again. So chose a good compromise between updating often (to make
# sure you always have the latest image) and rarely (to not waste battery).
DEFAULTINTERVAL=15




# Schedule for updating the screensaver. Use checkschedule.sh to check whether
# the format is correctly understood. 
#
# The format is a space separated list of settings for different times of day:
#       SCHEDULE="setting1 setting2 setting3 etc"
# where each setting is of the format
#       STARTHOUR:STARTMINUTE-ENDHOUR:ENDMINUTE=INTERVAL
# where
#       STARTHOUR:STARTMINUTE is the time this setting starts taking effect
#       ENDHOUR:ENDMINUTE is the time this setting stops being active
#       INTERVAL is the interval in MINUTES in which to update the screensaver
#
# Time values must be in 24 hour format and not wrap over midnight.
# EXAMPLE: "00:00-06:00=480 06:00-18:00=15 18:00-24:00=30"
#          -> Between midnight and 6am, update every 4 hours
#          -> Between 6am and 6pm (18 o'clock), update every 15 minutes
#          -> Between 6pm and midnight, update every 30 minutes
#
# Use the checkschedule.sh script to verify that the setting is correct and
# which would be the active interval.
#SCHEDULE="00:00-06:00=480 06:00-18:00=1 18:00-24:00=1" #debug
SCHEDULE="00:00-08:00=60 08:00-10:00=15 10:00-24:00=30" #normal


# URL of screensaver image. This really must be in the EXACT resolution of
# your Kindle's screen (e.g. 600x800 or 758x1024) and really must be PNG.
#IMAGE_URI="http://marcteyssier.com/experiment/epaperWeatherApi/weather-image.png"
IMAGE_URI=$SERVERDIR"/weatherImage.png"
GENERATION_URI=$SERVERDIR"/getImage.php"


# Automatically go in screen saver mode by triggering a fake button click
FORCE_SCREENSAVER=0

# Set prevent screen saver 
PREVENT_SCREENSAVER=1 

# Retry 
DONOTRETRY=0

# Folder that holds the screensavers. This can be any folder
SCREENSAVERFOLDER=/mnt/us/linkss/screensavers/

# In which file to store the downloaded image. Make sure this is a valid
# screensaver file. E.g. check the current screensaver folder to see what
# the first filename is, then just use this. THIS FILE WILL BE OVERWRITTEN!
# This could be any file as long as it ends with .png
SCREENSAVERFILE=$SCREENSAVERFOLDER/bg_medium_ss00.png

# Whether to create log output (1) or not (0).
LOGGING=1

# Where to log to - either /dev/stderr for console output, or an absolute
# file path (beware that this may grow large over time!)
L#OGFILE=/dev/stderr
LOGFILE=${SCRIPTDIR}/onlinescreensaver.log

# whether to disable WiFi after the script has finished (if WiFi was off
# when the script started, it will always turn it off)
DISABLE_WIFI=1

# Domain to ping to test network connectivity. Default should work, but in
# case some firewall blocks access, try a popular local website.
TEST_DOMAIN="www.google.com"

# How long (in seconds) to wait for an internet connection to be established
# (if you experience frequent timeouts when waking up from sleep, try to
# increase this value)
NETWORK_TIMEOUT=30



#############################################################################
# Battery
#############################################################################

# Add Batt level to URI as query string, to display the icon on the server
DO_BATTERY_QUERYSTRING=1 

# Bellow this value, the battery will be sent to query string
BATTERY_LOW_QUERYSTRING=30




# Show battery level
BATTERY_TEXT_DISPLAY=1 

# Image to display
BATTERY_LOW_IMAGE="${SCRIPTDIR}/low_battery.png"


BATTERY_LOW=5






#############################################################################
# Advanced
#############################################################################


# /!\ Currently hard coded in the wait_for() function in utils.sh
USE_RTC=0 # if 0, only sleep will be used (which is useful for debugging)
# /!\ Currently hard coded in the wait_for() function in utils.sh
# the real-time clock to use (0, 1 or 2)
RTC=1

# the temporary file to download the screensaver image to
TMPFILE=${SCRIPTDIR}/tmp.onlinescreensaver.png