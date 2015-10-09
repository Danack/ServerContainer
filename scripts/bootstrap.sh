#!/bin/bash

# set -eux -o pipefail
set -x

environment="centos,live"

if [ "$#" -ge 1 ]; then
    environment=$1
fi

intahwebzGroup="www-data"

cd /home/github/ServerContainer/ServerContainer
yum localinstall -y lib/mysql-community-release-el6-5.noarch.rpm


mkdir -p /home/servercontainer

cd /home/github/ServerContainer/ServerContainer/scripts

bash ./build/importBaserealityGPGPublicKey.sh
bash ./build/addBaserealityRPMRepo.sh
bash ./build/installPackages.sh
bash ./build/configureIPTables.sh
bash ./build/createGroup.sh
bash ./build/setupComposer.sh

cd /home/github/ServerContainer/ServerContainer

oauthtoken=`php bin/info.php "github.access_token"`
composer config -g github-oauth.github.com $oauthtoken


sh scripts/build/configurateTemplates.sh $environment

cd /home/github/ServerContainer/ServerContainer/scripts

usermod -a -G www-data nginx

users=( )
users+=("basereality")
users+=("blog")
users+=("imagickdemos")
users+=("intahwebz")
users+=("servercontainer")

for user in "${users[@]}"
do
   :
   . ./build/createUser.sh $user
done


/etc/init.d/mysqld start
. ./build/configureMySQL.sh intahwebz pass123 pass123

#cp /home/github/intahwebz/intahwebzConf.php /home/intahwebz/intahwebzConf.php
#ln -s /home/github/ServerContainer/clavis.php /home/servercontainer/clavis.php 

nginx
/etc/init.d/php-fpm start
/etc/init.d/redis start
/etc/init.d/supervisord start

echo "imagick.test 127.0.0.1" >> /etc/hosts