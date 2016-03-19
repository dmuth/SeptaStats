#!/bin/bash
#
# This script is used to run a query against Splunk
#

#
# Errors are fatal
# 
set -e
#set -x # Debugging


UN="admin"
PW="adminpw"

#
# Our Splunk endpoint
#
URL="https://localhost:8089/services/search/jobs"

#
# Default query, this can be overridden
#
QUERY="search index=septa_analytics earliest=-5m | head limit=10"

#
# Do we want to export the raw data?
#
EXPORT=""


if test "$1" == "-h" -o "$1" == "--help"
then
	>&2 echo "! "
	>&2 echo "! Syntax: $0 [--export] query"
	>&2 echo "! "
	exit 1

elif test "$1" == "--export"
then
	EXPORT=1
	QUERY=$2

elif test "$1"
then
	QUERY=$1

fi


>&2 echo "# " 
>&2 echo "# Executing query: "
>&2 echo "# "
>&2 echo "# 	${QUERY}"
>&2 echo "# "
if test "$EXPORT"
	then
	>&2 echo "# "
	>&2 echo "# (The results will be exported in JSON format)"
	>&2 echo "# "
fi


# xmllint is part of the libxml2-utils package
RESULT=$(curl -4 -s -u ${UN}:${PW} -k ${URL} -d search="${QUERY}" )
JOBID=$(echo $RESULT | xmllint --xpath "/response/sid/text()" - || true)

if test ! "${JOBID}"
then
	>&2 echo "! "
	>&2 echo "! No Job ID found! "
	>&2 echo "! "
	>&2 echo "! Things to look for:"
	>&2 echo "! - Are percent signs encoded as '%25'?"
	>&2 echo "! - Does the query start with 'search'?"
	>&2 echo "! "

	exit 1
fi


>&2 echo "# "
>&2 echo "# Got Job ID: ${JOBID}"
>&2 echo "# "

>&2 echo "# Fetching results... "
>&2 echo "# "


#
# Get the state of our job, and keep looping as long as it's not done.
#
KEY=".entry[0].content.dispatchState"
STATE=$(curl -4 -s -u ${UN}:${PW} -k ${URL}/${JOBID} --get -d output_mode=json | jq ${KEY})

while true
do
	if test ${STATE} == '"DONE"'
	then
		break
	fi

	>&2 echo "# State is ${STATE}. Sleeping and trying again..."
	STATE=$(curl -4 -s -u ${UN}:${PW} -k ${URL}/${JOBID} --get -d output_mode=json | jq ${KEY})
	sleep 1

done


if test ! "$EXPORT"
then
	curl -4 -s -u ${UN}:${PW} -k ${URL}/${JOBID}/results --get -d output_mode=json

else
	#
	# Run our query through some filters so we just get the raw rows
	#
	curl -4 -s -u ${UN}:${PW} -k ${URL}/${JOBID}/results --get -d output_mode=json \
		| jq .results[]._raw \
		| sed -e "s/^\"//" -e "s/\"$//" -e 's/\\//g'

fi



