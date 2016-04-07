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

	echo "# "
	echo "# Testing method '${VERB}' on URL '${URL}/${URI}'"
	echo "# "
	curl -s -X${VERB} -I ${URL}/${URI} | egrep "Access-Control|^HTTP"
	echo

}


test GET /
test OPTIONS /

test GET /api/current/system
test OPTIONS /api/current/system

echo "# "
echo "# All done!"
echo "# "

