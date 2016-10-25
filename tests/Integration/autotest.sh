#!/usr/bin/env bash

SETUP_SCRIPTS_DIR="setup-scripts"

cd "$(dirname "$0")"
TESTSCRIPTS=`find ./Lib/ -name "*.php"`
for SCRIPT in ${TESTSCRIPTS}
do
    CMD="./run-test.sh $SCRIPT"
    echo "$CMD"
    ${CMD} || exit $?
    printf "\n"
done
