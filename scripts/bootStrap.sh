#!/bin/bash

#homedir="/home/github/ServerContainer/ServerContainer"
homeDir="/home/servercontainer/servercontainer"


intahwebzGroup="www-data"

cd ${homeDir}
yum localinstall -y lib/mysql-community-release-el6-5.noarch.rpm

cd ${homeDir}/scripts

. ./build/importBaserealityGPGPublicKey.sh
. ./build/addBaserealityRPMRepo.sh
. ./build/installPackages.sh
. ./build/configureIPTables.sh
. ./build/createGroup.sh
. ./build/setupComposer.sh

cd ${homeDir}

su -c servercontainer "sh bootStrapAsUser.sh"

# need to run autogen/addConfig.sh

cd ${homeDir}/scripts

/etc/init.d/mysqld start
. ./build/configureMySQL.sh intahwebz pass123 pass123

usermod -a -G www-data nginx

users=( )
users+=("imagickdemos")
users+=("intahwebz")
users+=("servercontainer")

for user in "${users[@]}"
do
   :
   . ./build/createUser.sh $user
done


nginx
/etc/init.d/php-fpm start
/etc/init.d/redis start


echo "imagick.test 127.0.0.1" >> /etc/hosts