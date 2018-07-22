#!/bin/bash
#
# This script is used to import data into Splunk with the "oneshot" command.
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


if test "$1" == "" -o "$1" == "-h" -o "$1" == "--help"
then
	>&2 echo "! "
	>&2 echo "! Syntax: $0 filename"
	>&2 echo "! "
	>&2 echo "! filename - The file to import.  This should be in JSON format."
	>&2 echo "! "
	exit 1

fi

FILE=$1

>&2 echo "# "
>&2 echo "# Importing file '${FILE}'..."
>&2 echo "# "

/var/splunk/bin/splunk add oneshot $FILE -index septa_analytics -sourcetype regional_rail


