#!/bin/bash

# set -eux -o pipefail

if [ "$#" -ne 1 ]; then
    echo "Illegal number of parameters, should be 'createUser username'"
    exit -1
fi

username=$1
group="www-data"

egrep -i -q "^${username}:" /etc/passwd

if [ $? -eq 0 ]; then
   echo "User ${username} already exists."
else
   echo "User ${username} does not exist, creating."
   useradd --key UMASK=0022 -m -g $group $username
fi

# todo - forbid remote login on centos 
# http://wiki.centos.org/HowTos/Network/SecuringSSH#head-b726dd17be7e9657f8cae037c6ea70c1a032ca1f
