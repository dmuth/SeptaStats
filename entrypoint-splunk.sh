#!/bin/bash
#
# Script to run from Splunk
#


TZ="${TZ:=EST5EDT}"


if test ! "$SPLUNK_PASSWORD"
then
	echo "! " 
	echo "! You need to specify a default Splunk password in the SPLUNK_PASSWORD variable to continue!" 
	echo "! "
	exit 1

elif test "$SPLUNK_PASSWORD" == "password"
then
	echo "! "
	echo "! Seriously?  Your Splunk password is 'password'!?"
	echo "! "
	echo "! Nope, we're not doing this.  I'm sorry, but if you're going to put "
	echo "! a Splunk instance on the Internet, I need you to choose a better password."
	echo "! "
	echo "! Try https://diceware.dmuth.org/ if you need help creating a password that's easy to remember. :-)"
	echo "! "
	echo "! "
	exit 1

fi




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



