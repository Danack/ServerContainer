#!/bin/bash

intahwebzGroup="www-data"


cd /home/github/ServerContainer/ServerContainer/scripts

. ./build/aliasPHP.sh
. ./build/importBaserealityGPGPublicKey.sh
. ./build/addBaserealityRPMRepo.sh
. ./build/installPackages.sh
. ./build/configureIPTables.sh
. ./build/createGroup.sh

usermod -a -G www-data nginx

users=( )
users+=("imagickdemos")
users+=("intahwebz")
users+=("servercotainer")

for user in "${users[@]}"
do
   :
   . ./createUser.sh $user
done


