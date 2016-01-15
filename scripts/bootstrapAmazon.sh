#!/bin/bash

set -x #echo on


. ./scripts/build/createGroup.sh
. ./scripts/build/createUser.sh "servercontainer"


mkdir -p /home/servercontainer/servercontainer

cp -Rf /tmp/servercontainer/ServerContainer-master/* /home/servercontainer/servercontainer

find /home/servercontainer -type d -exec chmod 755 {} \;
find /home/servercontainer -type f -exec chmod 755 {} \;
chown -R servercontainer:www-data /home/servercontainer
cd /home/servercontainer/servercontainer
sh scripts/bootstrap.sh


nginx -s reload