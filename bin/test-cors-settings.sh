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
	echo "! Syntax: $0 [https://]BASE_URL[:PORT]"
	echo "! "
	exit 1
fi

URL=$1


#
# Our wrapper for testing.
#
function test() {

	VERB=$1
	URI=$2

	TARGET="${URL}${URI}"

	echo "# "
	echo "# Testing method '${VERB}' on URL '${TARGET}'"
	echo "# "
	curl -s -X${VERB} -I ${TARGET} | egrep "Access-Control|^HTTP"
	echo

}


test GET /
test GET /api
test GET /api/current/trains
test GET /api/current/train/521
test GET /api/current/train/521/history
test GET /api/current/train/521/history/average
test GET /api/current/train/521/latest
test GET /api/current/train/521,553/latest
test GET /api/current/train/587,553,521,591,589,470,472,474,476/latest
test GET /api/current/system
test GET /api/current/system/latest
test GET /api/current/system/latest/stats
test GET /api/current/system/totals
test GET /api/current/lines
test GET /api/current/line/paoli-thorndale/outbound
test GET /api/current/line/paoli-thorndale/inbound
test GET /api/current/line/paoli-thorndale/inbound/latest
test GET /api/current/line/paoli-thorndale/foobar
test GET /api/current/line/foobar/foobar
test GET /api/current/station/ardmore/trains
test GET /api/current/station/ardmore/trains/latest
test GET /api/current/station/ardmore/stats
test GET /api/current/stations

test OPTIONS /
test OPTIONS /api
test OPTIONS /api/current/system


echo "# "
echo "# All done!"
echo "# "

