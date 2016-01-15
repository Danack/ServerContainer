#!/bin/bash


set -x

mkdir -p /home/servercontainer

rm -rf /tmp/servercontainer/ServerContainer-master
cd /tmp/servercontainer
curl -L "https://github.com/Danack/ServerContainer/archive/master.tar.gz" -o "master.tgz"
tar -xvf master.tgz
cd ./ServerContainer-master
sh ./scripts/bootstrapAmazon.sh

