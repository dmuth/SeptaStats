#!/bin/bash
#
# Export our SEPTA Stats for today and yesterday and back them up to S3.
#

# Errors are fatal
set -e

LOOP_SECONDS=${LOOP_SECONDS:=900}
YESTERDAY_BACKUP_INTERVAL=4


#
# Change to the directoy where this script is.
# We have this here in part so that we can run the script while in the container
# for development.
#
pushd $(dirname $0) > /dev/null

if test ! "$S3"
then
	echo "! "
	echo "! Environment variable \"S3\" needs to be set with the S3 bucket to backup to!"
	echo "! "
	exit 1
fi

AWS_CREDS=$HOME/.aws/credentials
if test ! -f $AWS_CREDS
then
	echo "! "
	echo "! AWS Credentials not found in $AWS_CREDS!  Stopping."
	echo "! "
	exit 1
fi



echo "# "
echo "# Starting Backup script"
echo "# "
echo "# Available env vars: LOOP_SECONDS, YESTERDAY_BACKUP_INTERVAL, S3"
echo "# "
echo "# Backing up to S3 location: ${S3}"
echo "# Looping this many seconds: ${LOOP_SECONDS}"
echo "# Number of loop between backups of yesterday: ${YESTERDAY_BACKUP_INTERVAL}"
echo "# "
echo "# "


YESTERDAY_BACKUPS_LEFT=1

while true
do

	FILE_TODAY=$(date +%Y%m%d).txt.gz
	FILE_YESTERDAY=$(date --date yesterday +%Y%m%d).txt.gz

	TODAY_START=$(date +%m/%d/%Y:00:00:00)
	TODAY_END=$(date +%m/%d/%Y:23:59:60)
	YESTERDAY_START=$(date --date yesterday +%m/%d/%Y:00:00:00)
	YESTERDAY_END=$(date --date yesterday +%m/%d/%Y:23:59:60)

	TARGET_TODAY="${S3}${FILE_TODAY}"
	TARGET_YESTERDAY="${S3}${FILE_YESTERDAY}"

	TMP=$(mktemp /tmp/backup-XXXXXXX)
	TMP_GZ=$(mktemp /tmp/backup-gz-XXXXXXX)

	#QUERY="index=septa_analytics earliest=-4h | tail limit=10" # Debugging

	QUERY="index=septa_analytics earliest=\"${TODAY_START}\" latest=\"${TODAY_END}\""
	/mnt/bin/splunk-query.sh "search $QUERY" > $TMP
	echo "# Compressing backup..."
	cat $TMP | gzip -v > $TMP_GZ

	TARGET=$TARGET_TODAY
	echo "# Backing up today to S3 ($TARGET)..."
	aws s3 cp $TMP_GZ $TARGET


	#
	# We're only backing up yesterday every so many loops.
	# A backup of yesterday will always be made on the first run, and then infrequently
	# thereafter.  The reason for the infrequent backups is 
	#
	YESTERDAY_BACKUPS_LEFT=$(( $YESTERDAY_BACKUPS_LEFT - 1 ))
	echo "# "
	echo "# We have ${YESTERDAY_BACKUPS_LEFT} loops until we make a backup of yesterday..."
	echo "# "

	if test $YESTERDAY_BACKUPS_LEFT -le 0
	then

		echo "# Okay, we're making a backup of yesterday!"

		QUERY="index=septa_analytics earliest=\"${YESTERDAY_START}\" latest=\"${YESTERDAY_END}\""
		/mnt/bin/splunk-query.sh "search $QUERY" > $TMP
		echo "# Compressing backup..."
		cat $TMP | gzip -v > $TMP_GZ

		TARGET=$TARGET_YESTERDAY
		echo "# Backing up yesterday to S3 ($TARGET)..."
		aws s3 cp $TMP_GZ $TARGET

		YESTERDAY_BACKUPS_LEFT=$YESTERDAY_BACKUP_INTERVAL

	fi


	#
	# Remove our temp file
	#
	rm -f $TMP $TMP_GZ

	echo "Sleeping for ${LOOP_SECONDS} seconds..."
	sleep ${LOOP_SECONDS}

done


