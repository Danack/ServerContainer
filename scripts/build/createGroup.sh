#!/bin/bash

# set -eux -o pipefail

group="www-data"

set +x
egrep -i -q "^${group}" /etc/group
set -x

if [ $? -eq 0 ]; then
   echo "Group ${group} exists"
else
   echo "Group ${group} does not exist, creating."
   groupadd -r $group
fi
