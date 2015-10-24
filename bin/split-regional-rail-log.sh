#!/bin/bash
#
# This script is used to split up a log that was created with 
# get-regional-rail-trainview.sh and store it in a log file that 
# can then be loaded into Splunk.  This is useful for uploading old
# train data to a separate Splunk instance.
#

#
# Errors are fatal
#
set -e


if test ! "$1"
then
	echo "! "
	echo "! Syntax: $0 file_to_read"
	echo "! "
	exit
fi

FILENAME=$1


#
# Sanity check to see if jq is present.
#
if test ! $(which jq)
then
	echo "! "
	echo "! $0: Error! You need the 'jq' utility installed!"
	echo "! "
	echo "! This can likely be installed with apt-get or yum, or from the webite at: "
	echo "!		https://stedolan.github.io/jq/"
	echo "! "
	exit 1
fi

IFS="
"
for LINE in $(cat $FILENAME)
do
	TIMESTAMP=$(echo $LINE | cut -d" " -f1-2)
	DATA=$(echo $LINE | cut -d" " -f3-)

	#
	# How many elements in this string?
	#
	LEN=$(echo $DATA | jq length)

	#
	# Now loop through those arrays, and print each array on a line.
	# This makes the data easier to digest in Splunk, as we'll have a 
	# separate event for each train.
	#
	END=$(($LEN - 1))
	for I in $(seq 0 $END)

	do
		#
		# Write to stdout so that Splunk picks it up
		# We're also going insert the timestamp here.  This is so that if
		# we use the "dump" command to export that data from Splunk in 
		# the future, we'll have timestamps.
		#
		echo $DATA | jq .[${I}] | sed -e s/"{"/"{\"_timestamp\": \"${TIMESTAMP}\", "/ | tr -d "\n"
		echo ""

	done

done


