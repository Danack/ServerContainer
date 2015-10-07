#!/bin/bash

# set -eux -o pipefail
set -x

group="www-data"
set +e
egrep -i -q "^${group}" /etc/group

result=$?

set -e

if [ $result -eq 0 ]; then
   echo "Group ${group} exists"
else

   echo "Group ${group} does not exist, creating."
   groupadd -r $group
fi


echo "createGroup $0 complete"