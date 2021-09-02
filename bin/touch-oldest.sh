#!/bin/bash

#
# Keep the oldest jpg to life by touching it, or renaming it to something recent.
#

# Delay in seconds between rotations
let delay=5
ROTATE_DIR=$1

if [[ -z $ROTATE_DIR || ! -d $ROTATE_DIR ]]; then
    echo "Missing dir to rotate image files in."
    exit 1
fi
cd $ROTATE_DIR
if ! ls *.jpg > /dev/null 2>&1;  then
    echo "No jpg files found"
    exit 1
fi

function rotate_oldest {
    local oldest
    oldest=$(/bin/ls -t1 *.jpg | tail -1)
    #cp -v $oldest camera-$(date +%FT%H%M%S).jpg
    echo Touching $oldest
    touch $oldest
    sleep $delay
}

while true; do
    rotate_oldest
done
