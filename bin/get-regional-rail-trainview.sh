#!/bin/bash
#
# Fetch our Regional Trail train info.
#
# A full list of API endpoints can be found at http://www3.septa.org/hackathon/
#

#
# Errors are fatal
#
set -e


#
# Where are we logging our JSON?
#
HERE=$(dirname $0)
LOG="${HERE}/train-log.txt"


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

#
# Check to see if ts is installed
#
if test ! $(which ts) 
then
	echo "! "
	echo "! $0: Error! You need the 'ts' utility installed!"
	echo "! "
	echo "! This can likely be installed with apt-get or yum, from the 'moreutils' package."
	echo "! "
	exit
fi


while true
do

	#
	# Grab our JSON
	#
	OUTPUT=$(curl -s http://www3.septa.org/hackathon/TrainView/)
	LEN=$(echo $OUTPUT | jq length)

	#
	# Store our complete output to the log in case we need it later.
	#
	echo $OUTPUT | ts >> $LOG

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
		#
		echo $OUTPUT | jq .[${I}] | tr -d "\n"
		echo ""

	done


	sleep 50

done


