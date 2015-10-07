#!/bin/bash


mkdir -p /home/servercontainer/servercontainer

cd /tmp
curl -L "https://github.com/Danack/ServerContainer/archive/master.tar.gz" -o "master.tgz"
tar -xvf master.tgz
cd ./ServerContainer-master/scripts
sh ./bootstrap/bootstrap.sh

