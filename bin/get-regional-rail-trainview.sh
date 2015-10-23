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

	curl http://www3.septa.org/hackathon/TrainView/

	sleep 60

done


