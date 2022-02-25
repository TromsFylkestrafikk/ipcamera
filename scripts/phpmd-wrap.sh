#!/bin/sh

PROJECT_ROOT=$(dirname $(dirname $(realpath $0)))

for file in "$@"; do
    $PROJECT_ROOT/vendor/bin/phpmd "$file" ansi $PROJECT_ROOT/phpmd.xml;
    EXIT_CODE=$?
    if [ "$EXIT_CODE" -ne "0" ]; then
        echo "Exiting!"
        exit $EXIT_CODE
    fi
done
