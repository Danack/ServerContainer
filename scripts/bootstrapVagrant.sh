#!/bin/bash

set -x

environment="dev,centos_guest"

#echo "This is bootstrap vagrant"

#FILE="$( readlink -e "${BASH_SOURCE[0]}")"
#DIR="$( dirname "${FILE}" )"
# CHDIR="$( cd ${DIR} && pwd )"

DIR=/home/github/ServerContainer/ServerContainer

pushd $DIR

echo " curr dir is $DIR"
pwd

bash ./scripts/bootstrap.sh "${environment}"
popd