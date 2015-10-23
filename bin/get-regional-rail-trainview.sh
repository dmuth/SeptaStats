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


while true
do

	#
	# Grab our JSON
	#
	OUTPUT=$(curl -s http://www3.septa.org/hackathon/TrainView/)
	LEN=$(echo $OUTPUT | jq length)

	#
	# Now loop through those arrays, and print each array on a line.
	# This makes the data easier to digest in Splunk, as we'll have a 
	# separate event for each train.
	#
	END=$(($LEN - 1))
	for I in $(seq 0 $END)
	do
		echo $OUTPUT | jq .[${I}] | tr -d "\n"
		echo ""
	done


	sleep 50

done


