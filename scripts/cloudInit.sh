#!/bin/bash


mkdir -p /home/servercontainer/servercontainer

rm -rf /tmp/servercontainer
mkdir -p /tmp/servercontainer
cd /tmp/servercontainer
curl -L "https://github.com/Danack/ServerContainer/archive/master.tar.gz" -o "master.tgz"
tar -xvf master.tgz
cd ./ServerContainer-master/scripts
sh ./bootstrap.sh

