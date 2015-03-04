#!/bin/bash

intahwebzGroup="www-data"

cd /home/github/ServerContainer/ServerContainer
yum localinstall -y lib/mysql-community-release-el6-5.noarch.rpm

cd /home/github/ServerContainer/ServerContainer/scripts

. ./build/aliasPHP.sh
. ./build/importBaserealityGPGPublicKey.sh
. ./build/addBaserealityRPMRepo.sh
. ./build/installPackages.sh
. ./build/configureIPTables.sh
. ./build/createGroup.sh



cd /home/github/ServerContainer/ServerContainer
php vendor/bin/configurate -p data/config.php data/my.cnf.php autogen/my.cnf.conf $environment
php vendor/bin/configurate -p data/config.php data/addConfig.sh.php autogen/addConfig.sh $environment

cd /home/github/ServerContainer/ServerContainer/scripts

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