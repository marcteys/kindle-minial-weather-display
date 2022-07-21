#!/bin/sh
EIPS=/usr/sbin/eips
$EIPS -p
sleep 1
$EIPS 11 18 "                          "
$EIPS 11 19 "   RUNME.sh is running.   "
$EIPS 11 20 "                          "
sleep 3
