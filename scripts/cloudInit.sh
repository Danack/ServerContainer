#!/bin/bash


mkdir -p /home/servercontainer
mkdir -p /tmp/servercontainer


#put clavis.php in /tmp/servercontainer/clavis.php and /home/servercontainer/clavis.php



if [ ! -f /tmp/servercontainer/clavis.php ]
  then
    echo "You forgot to create /tmp/servercontainer/clavis.php"
    exit -1
fi

if [ ! -f /home/servercontainer/clavis.php ]
  then
    echo "You forgot to create /tmp/servercontainer/clavis.php"
    exit -1
fi




rm -rf /tmp/servercontainer/ServerContainer-master
cd /tmp/servercontainer
curl -L "https://github.com/Danack/ServerContainer/archive/master.tar.gz" -o "master.tgz"
tar -xvf master.tgz
cd ./ServerContainer-master
sh ./scripts/bootstrap.sh

