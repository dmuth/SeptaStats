#!/bin/bash
#
# Upload our files to the AWS S3 bucket
#

TARGET="s3://septastats.com"

# Errors are fatal
set -e

#
# Change to the directory where this script is hosted.
#
pushd $(dirname $0) >/dev/null


function markdown_to_html() {

    echo "# "
    echo "# Converting Markdown to HTML..."
    echo "# "
    pandoc -s index.md -c style.css -o index.html

} # End of markdown_to_html()


function sync_to_s3() {

    echo "# "
    echo "# Syncing to ${TARGET}..."
    echo "# "
    aws s3 sync . ${TARGET}
    #aws s3 sync . ${TARGET} --dryrun # Debugging
    #aws s3 ls ${TARGET} # Debugging

} # End of sync_to_s3()


function invalidate_cloudfront_cache() {

    HOSTNAME="septastats.com"   
    echo "# Getting CloudFront Distribution for '${HOSTNAME}'..."

    ID=$(aws cloudfront list-distributions \
        --query "DistributionList.Items[?Aliases.Items[?contains(@, '${HOSTNAME}')]]" \
        | jq -r .[].Id)

    if test ! "${ID}"
    then
        echo "! No CloudFront distribution ID found, something has gone wrong.  Aborting!"
        exit 1
    fi

    echo "# Found Cloudfront distribution ID: ${ID}"
    echo "${ID}" > .cloudfront-distribution-id

    echo "# Invalidating cache... "
    aws cloudfront create-invalidation --distribution-id ${ID} --paths "/*"

    echo "# Getting current invalidations..."
    echo
    ./get-cloudfront-cache-invalidations.sh ${ID} | head -n5
    echo

    echo "# "
    echo "# To keep track of invalidation status so you know when complete, run this command:"
    echo "# ./get-cloudfront-cache-invalidations.sh | head -n5"
    echo "# "

} # End of invalidate_cloudfront_cache()

# Main entry point
markdown_to_html
sync_to_s3
invalidate_cloudfront_cache
