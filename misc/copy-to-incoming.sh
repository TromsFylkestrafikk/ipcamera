#!/bin/bash

#
# Copy pictures from directory to camera's incoming dir.
#

# Delay between each copied file
let delay=5

# Number of loops
let loops=1

infinite=0

function usage() {
    echo "Synopsis: $name [OPTIONS] <SOURCEDIR> <DESTDIR>

Copy jpegs from SOURCEDIR to DESTDIR with delay between each copy.

OPTIONS
    -d, --delay=SECONDS     Seconds between each copy. Default: $delay
    -l, --loops=COUNT       Number of loops. Default: $loops
    -i, --infinite          Runs forever. Ignores --loops
    -h, --help              Show this help text
"
}

name=$(basename $0)
OPTS=$(getopt --options 'd:ihl:' --long 'delay:,infinite,help,loops:' -n $name -- "$@")

if [ $? -ne 0 ]; then
    usage
    echo 'Exiting...' >&2
    exit 1
fi

eval set -- "$OPTS"
unset OPTS

while true; do
    case "$1" in
        -d|--delay)
            let delay=$2
            shift
            ;;
        -i|--infinite)
            infinite=1
            ;;
        -l|--loops)
            let loops=$2
            shift
            ;;
        -h|--help)
            usage
            exit
            ;;
        --)
            shift
            break
            ;;
        esac
    shift
done

from=$1
to=$2

if [[ ! -d $from || ! -d $to ]]; then
    echo "Missing from or to directories" >&2
    usage
    exit 1
fi

function copy_files() {
    for picture in $(ls -tr1 $from/*.{jpg,jpeg}); do
        cp -v $picture $to/dummy-$(date +%F_%H:%M:%S).jpg
        sleep $delay
    done
}

while [[ $infinite == '1' || $loops > 0 ]]; do
    copy_files
    loops=$(($loops - 1))
done
