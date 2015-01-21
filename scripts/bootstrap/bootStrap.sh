#!/bin/bash

# On a Amazon linux image we can use this to log console information to a file
# this doesn't appear to work on the Centos image we're using.
#delete this exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/tty0) 2>&1
# exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

intahwebzGroup="www-data"

cd /tmp

wget -O master.tgz https://github.com/Danack/ServerContainer/archive/master.tar.gz

tar -xvf master.tgz

cd ./ServerContainer-master/scripts

echo "before bootstrapEC2"
. ./bootstrapEC2.sh

echo "after bootstrapEC2"




