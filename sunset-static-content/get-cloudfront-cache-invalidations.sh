#!/bin/bash
#
# This script lists Cloudfront invalidations for a given distribution ID.
#

# Errors are fatal
set -e

ID_FILE=".cloudfront-distribution-id"

ID=""
if test ! "$1"
then

  ID=$(cat ${ID_FILE} 2>/dev/null || true)

  if test ! "${ID}"
  then
    echo "! "
    echo "! Syntax: $0 ID"
    echo "! "
    exit 1
  fi

  echo "# Read ID ${ID} from file '${ID_FILE}'"

else
  ID=$1

fi


aws cloudfront list-invalidations \
    --distribution-id ${ID} \
    --query "InvalidationList.Items[*].[Id,CreateTime,Status]" \
    --output text

