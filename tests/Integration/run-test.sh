#!/bin/bash

if [ $1 ] ; then
  TESTSCRIPT=$1
else
  echo "No test file given"                                                                                                                                                    exit
fi

if [ ! -e "$TESTSCRIPT" ] ; then
    echo "Test file does not exist"
    exit
fi

# sleep is necessary, otherwise the LDAP server cannot be connected to, yet.
WEBUSER=`ls -ld ../../../../config/config.php | awk '{print $3}'`
OUT=""
setup-scripts/start.sh > /dev/null && sleep 5 && \
    if [ "$WEBUSER" == "travis" ]; then
	../../../../occ app:enable user_ldap && \
	OUT=$(php -f "$TESTSCRIPT")
	echo "$OUT"
    else
	sudo -u "$WEBUSER" ../../../../occ app:enable user_ldap && \
	OUT=$(sudo -u "$WEBUSER" php -f "$TESTSCRIPT")
	echo "$OUT"
    fi
CODE=$?
setup-scripts/stop.sh > /dev/null
if [ $CODE -eq 0 ]; then
    # ownCloud likes to catch errors, resulting in an exit code of 0.
    # so we add an extra check whether tests really succeeded
    if [[ $OUT != *"Tests succeeded"* ]]; then
        echo "a superordinate error occurred – please check the owncloud.log"
        echo "disaplying the last 50 lines of owncloud log"
        tail -n 50 ../../../../data/owncloud.log
        CODE=1
    fi
fi
exit ${CODE}
