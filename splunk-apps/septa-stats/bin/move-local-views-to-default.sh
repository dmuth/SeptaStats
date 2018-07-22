#!/bin/bash
#
# Move views in local/ to default/
#

set -e # Errors are fatal

SOURCE="local/data/ui/views"
TARGET="default/data/ui/views"


#
# Change to our app home directory
#
pushd $(dirname $0)/.. > /dev/null


echo "# "
echo "# Moving files from ${SOURCE} over into ${TARGET}..."
echo "# "

#ls -l $SOURCE # Debugging
#ls -l $TARGET # Debugging

#
# Send our files over!
#
mv -v $SOURCE/* $TARGET



