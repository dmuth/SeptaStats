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
PW=${SPLUNK_PASSWORD}

#
# Our Splunk endpoint
#
URL="https://splunk:8089/services/search/jobs"

#
# Default query, this can be overridden
#
QUERY="search index=septa_analytics earliest=-5m | head limit=10"


if test "$1" == "-h" -o "$1" == "--help"
then
	>&2 echo "! "
	>&2 echo "! Syntax: $0 [--export] query"
	>&2 echo "! "
	exit 1

elif test "$1"
then
	QUERY=$1

fi


>&2 echo "# " 
>&2 echo "# Executing query against ${URL}: "
>&2 echo "# "
>&2 echo "# 	${QUERY}"
>&2 echo "# "

echo $URL
curl -4 -s -u ${UN}:${PW} -k "${URL}/export" -d "search=${QUERY}" -d "output_mode=raw" 


