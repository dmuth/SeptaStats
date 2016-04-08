#!/bin/bash
#
# This script runs tests against the webserver to see if we are properly
# returning CORS headers.
#

# Errors are fatal
set -e

if test ! "$1"
then
	echo "! "
	echo "! Syntax: $0 [https://]BASE_URL[:PORT] [ verb ]"
	echo "! "
	exit 1
fi

URL=$1
VERB="GET"
if test "$2"
then
	VERB=$2
fi


#
# Our wrapper for testing.
#
function test() {

	URI=$1

	TARGET="${URL}${URI}"

	echo "# "
	echo "# Testing method '${VERB}' on URL '${TARGET}'"
	echo "# "
	curl -s -X${VERB} -I ${TARGET} | egrep "Access-Control|^HTTP"
	echo

}


test /
test /api
test /api/current/trains
test /api/current/train/521
test /api/current/train/521/history
test /api/current/train/521/history/average
test /api/current/train/521/latest
test /api/current/train/521,553/latest
test /api/current/train/587,553,521,591,589,470,472,474,476/latest
test /api/current/system
test /api/current/system/latest
test /api/current/system/latest/stats
test /api/current/system/totals
test /api/current/lines
test /api/current/line/paoli-thorndale/outbound
test /api/current/line/paoli-thorndale/inbound
test /api/current/line/paoli-thorndale/inbound/latest
test /api/current/line/paoli-thorndale/foobar
test /api/current/line/foobar/foobar
test /api/current/station/ardmore/trains
test /api/current/station/ardmore/trains/latest
test /api/current/station/ardmore/stats
test /api/current/stations



echo "# "
echo "# All done!"
echo "# "

