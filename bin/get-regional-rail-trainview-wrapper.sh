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


while true
do

	./get-regional-rail-trainview.php | tee -a $LOG

	sleep 50

done


