#!/bin/bash

set -x #echo on

. ./build/createGroup.sh

users=()
users+=("blog")
users+=("intahwebz")
users+=("servercontainer")

for user in "${users[@]}"
do
   :
   . ./build/createUser.sh $user
done

mkdir -p /home/servercontainer/servercontainer

cp -R /tmp/ServerContainer-master/* /home/servercontainer/servercontainer

find /home/servercontainer -type d -exec chmod 755 {} \;
find /home/servercontainer -type f -exec chmod 755 {} \;

chown -R servercontainer:www-data /home/servercontainer

cd /home/servercontainer/servercontainer

sh scripts/bootStrap.sh
