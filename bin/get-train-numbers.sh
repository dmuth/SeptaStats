#!/bin/bash
#
# This script will go through the schedules at http://www.septa.org/schedules/rail/index.html
# and parse out all of the train numbers.  It will then print out those numbers 
# and what line they're on.
#

# Errors are fatal
set -e



#
# Fetch a list of trains from SEPTA's website.
#
#
# This is a sample of the line we're grepping for
#
# <th height="33px" style="vertical-align: middle;">810<br /><img src="http://www.septa.org/site/images/filler.gif" width="51px" /></th>
#
function get_trains() {

	local LINE=$1
	local URL=$2

	local TRAINS=$(curl $URL |grep 33px |grep 51px |sed -e 's/.*middle;\">\([^<]*\).*/\1/')

	for TRAIN in $TRAINS
	do
		echo "$TRAIN, $LINE"
	done

} # End of get_trains()


#
# Loop through weekly, Saturday, and Sunday/holiday schedules.
#
for SCHEDULE in $(echo "w s h")
do

	#
	# If _1 is in the URL, that's inbound.
	# If _0 is in the URL, that's outbound.
	#
	URL="http://www.septa.org/schedules/rail/${SCHEDULE}/AIR_1.html"
	get_trains "Airport (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/${SCHEDULE}/AIR_0.html"
	get_trains "Airport (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/NOR_1.html"
	get_trains "Manayunk/Norristown (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/NOR_0.html"
	get_trains "Manayunk/Norristown (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/CHE_1.html"
	get_trains "Chestnut Hill East (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/CHE_0.html"
	get_trains "Chestnut Hill East (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/MED_1.html"
	get_trains "Media/Elwyn (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/MED_0.html"
	get_trains "Media/Elwyn (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/CHW_1.html"
	get_trains "Chestnut Hill West (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/CHW_0.html"
	get_trains "Chestnut Hill West (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/PAO_1.html"
	get_trains "Paoli/Thorndale (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/PAO_0.html"
	get_trains "Paoli/Thorndale (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/TRE_1.html"
	get_trains "Trenton (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/TRE_0.html"
	get_trains "Trenton (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/WAR_1.html"
	get_trains "Warminster (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/WAR_0.html"
	get_trains "Warminster (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/FOX_1.html"
	get_trains "Fox Chase (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/FOX_0.html"
	get_trains "Fox Chase (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/GLN_1.html"
	get_trains "Glenside (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/GLN_0.html"
	get_trains "Glenside (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/WTR_1.html"
	get_trains "West Trenton (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/WTR_0.html"
	get_trains "West Trenton (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/LAN_1.html"
	get_trains "Lansdale/Doylestown (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/LAN_0.html"
	get_trains "Lansdale/Doylestown (Outbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/WIL_1.html"
	get_trains "Wilmington/Newark (Inbound)" $URL

	URL="http://www.septa.org/schedules/rail/w/WIL_0.html"
	get_trains "Wilmington/Newark (Outbound)" $URL


done

URL="http://www.septa.org/schedules/rail/w/CYN_1.html"
get_trains "Cynwyd (Inbound)" $URL

URL="http://www.septa.org/schedules/rail/w/CYN_0.html"
get_trains "Cynwyd (Outbound)" $URL


