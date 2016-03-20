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
# Change into this script's directory
#
pushd $(dirname $0) > /dev/null


#
# Where are we logging our JSON?
#
HERE=$(dirname $0)
LOG="${HERE}/train-log.txt"

#
# I kept getting this error when running the script from Splunk:
# /opt/splunk/lib/libcrypto.so.1.0.0: version `OPENSSL_1.0.0' not found (required by php)
#
# According to:
#	https://answers.splunk.com/answers/185635/why-splunk-triggered-alert-is-not-working-for-my-s.html
#
# ...I need to put this in.  No idea why...
#
unset LD_LIBRARY_PATH

while true
do

	#./get-regional-rail-trainview.php | tee -a $LOG
	./get-regional-rail-trainview.php 

	sleep 50

done


