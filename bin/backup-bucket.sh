#!/bin/bash
#
# Backs up Splunk buckets to a specified location.
#


# Errors are fatal
set -e


#
# Syntax check.
#
if test ! "$2"
then
	echo "! "
	echo "! Syntax: $0 source_dir target_dir"
	echo "! "
	echo "! Backs up all buckets in the source directory to the target directory."
	echo "! The target directory will be created if it does not exist."
	echo "! "
	exit 1
fi

SOURCE=$1
TARGET=$2


if test ! -d $SOURCE
then
	echo "! $0: Source directory '$SOURCE' does not exist!"
	exit 1

fi

FIRST_CHAR=${TARGET:0:1}
if test "$FIRST_CHAR" != "/"
then
	echo "! "
	echo "! $0: The target ${TARGET} should be an absolute path! "
	echo "! If in doubt, prepend with \$PWD :-)"
	echo "! "
	exit 1
fi

#
# Go to our source directory
#
cd $SOURCE


#
# Now find all buckets under it
#
for DIR in $(find . -type d -name db_\*)

do
	#
	# What directory are we backing up?
	#
	DIR2=$(basename $DIR)

	#
	# Make sure our target directory exists and set our target filename
	#
	mkdir -p "$TARGET"
	TARGET_TGZ="${TARGET}/${DIR2}.tgz"

	#
	# Go into the parent directory and then tar up the child directory
	#
	pushd $DIR/.. > /dev/null

	TARGET_SANITIZED=$(echo $TARGET | sed -e "s/[^A-Za-z0-9]/_/g")
	TOUCH_FILE="${DIR2}/.backedup-${TARGET_SANITIZED}"

	if test -f $TOUCH_FILE
	then
		echo "# "
		echo "# The directory $DIR2 has already been backed up! Skipping..."
		echo "# "

	else 
		#
		# Now tar up the bucket
		#
		echo "# "
		echo "# Tarring up directory ${DIR2} to ${TARGET_TGZ}..."
		echo "# "

		tar cfz "$TARGET_TGZ" $DIR2
		touch $TOUCH_FILE

		echo "# "
		echo "# Done!"
		echo "# "

	fi

	#
	# Return to the previous directory
	#
	popd >/dev/null

done


#
# Change ownership to Ubuntu so that BitTorrent Sync can replicate it.
#
chown -R ubuntu:ubuntu "$TARGET"



