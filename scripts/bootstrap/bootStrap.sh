#!/bin/bash

set -x #echo on

# On a Amazon linux image we can use this to log console information to a file
# this doesn't appear to work on the Centos image we're using.
#delete this exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/tty0) 2>&1
# exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

#cd /tmp
#wget -O master.tgz https://github.com/Danack/ServerContainer/archive/master.tar.gz
#tar -xvf master.tgz
#cd ./ServerContainer-master/scripts

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
