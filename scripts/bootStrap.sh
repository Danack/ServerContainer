#!/bin/bash

# set -eux -o pipefail

set -x

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

su -l servercontainer -c "cd /home/servercontainer/servercontainer && sh scripts/bootStrapAsUser.sh"
sh autogen/addConfig.sh

cd ${homeDir}/scripts

/etc/init.d/mysqld start
. ./build/configureMySQL.sh intahwebz pass123 pass123

usermod -a -G www-data nginx

users=( )
users+=("blog")
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

echo "127.0.0.1 imagick.test" >> /etc/hosts
echo "127.0.0.1 phpimagick.test" >> /etc/hosts