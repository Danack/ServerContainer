#!/bin/bash

environment="dev,centos_guest"

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

pushd $DIR

bash ./bootstrap.sh "${environment}"
popd