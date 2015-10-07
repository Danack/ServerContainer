#!/bin/sh
# http://serverfault.com/questions/148341/linux-schedule-command-to-run-once-after-reboot-runonce-equivalent

# Copy this file to /usr/local/bin/runonce
# and add this entry to crontab
# @reboot     /usr/local/bin/runonce

mkdir -p /etc/local/runonce.d/ran

for file in /etc/local/runonce.d/*
do
    if [ ! -f "$file" ]
    then
        continue
    fi
    "$file"
    mv "$file" "/etc/local/runonce.d/ran/$file.$(date +%Y%m%dT%H%M%S)"
    logger -t runonce -p local3.info "$file"
done