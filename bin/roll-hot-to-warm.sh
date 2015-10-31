#!/bin/bash
#
# Roll hot buckets to warm.
# Generally this isn't needed, but it's handy to have a utility to 
# roll buckets before doing a manual backup, for example.
#

/var/splunk/bin/splunk _internal call /data/indexes/septa-analytics/roll-hot-buckets -auth admin:adminpw

