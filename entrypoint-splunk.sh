#!/bin/bash
#
# Script to run from Splunk
#


TZ="${TZ:=EST5EDT}"
SPLUNK_PASSWORD="${SPLUNK_PASSWORD:=password}"

#
# Set our default password
#
pushd /opt/splunk/etc/system/local/ >/dev/null

cat user-seed.conf.in | sed -e "s/%password%/${SPLUNK_PASSWORD}/" > user-seed.conf
cat web.conf.in | sed -e "s/%password%/${SPLUNK_PASSWORD}/" > web.conf

popd > /dev/null

if test -f /mnt/docker/splunk-config/passwd
then
	echo "# "
	echo "# Splunk passwd file found, importing that into Splunk!"
	echo "# "
	cp /mnt/docker/splunk-config/passwd /opt/splunk/etc/
fi


#
# Start Splunk
#
/opt/splunk/bin/splunk start --accept-license

echo "# "
echo "# Available env vars: TZ, SPLUNK_PASSWORD"
echo "# "
echo "# "
echo "# If your data is not persisted, be sure you ran this container with: "
echo "# "
echo "#		-v \$(pwd)/data:/opt/splunk/var/lib/splunk/defaultdb"
echo "# "
echo "# Timezone in UTC?  Specify your timezone with -e, such as:"
echo "# "
echo "# 	-e TZ=EST5EDT"
echo "# "
echo "# "


#
# Tail this file so that Splunk keeps running
#
tail -f /opt/splunk/var/log/splunk/splunkd_stderr.log



