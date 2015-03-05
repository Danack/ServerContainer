#!/bin/bash

#homedir="/home/github/ServerContainer/ServerContainer"
homedir="/home/github/servercontainer/ServerContainer"


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

oauthtoken=`php bin/info.php GITHUB_ACCESS_TOKEN`
composer config -g github-oauth.github.com $oauthtoken

php vendor/bin/configurate -p data/config.php data/my.cnf.php autogen/my.cnf.conf $environment
php vendor/bin/configurate -p data/config.php data/addConfig.sh.php autogen/addConfig.sh $environment
    
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

cp /home/github/intahwebz/intahwebzConf.php /home/intahwebz/intahwebzConf.php

ln -s /home/github/ServerContainer/clavis.php /home/servercontainer/clavis.php 


nginx
/etc/init.d/php-fpm start
/etc/init.d/redis start


echo "imagick.test 127.0.0.1" >> /etc/hosts