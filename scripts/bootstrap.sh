#!/bin/bash

# set -eux -o pipefail
set -x

environment="centos,live"

if [ "$#" -ge 1 ]; then
    environment=$1
fi

intahwebzGroup="www-data"

# This script is meant to be run in the root directory of servercontainer files
# mkdir -p /home/servercontainer

projectDir=`pwd`

yum localinstall -y lib/mysql-community-release-el6-5.noarch.rpm

bash ./scripts/build/importBaserealityGPGPublicKey.sh
bash ./scripts/build/addBaserealityRPMRepo.sh
bash ./scripts/build/installPackages.sh
bash ./scripts/build/configureIPTables.sh
bash ./scripts/build/createGroup.sh
bash ./scripts/build/setupComposer.sh

oauthtoken=`php bin/info.php "github.access_token"`
composer config -g github-oauth.github.com $oauthtoken

sh scripts/build/configurateTemplates.sh $environment

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
   . ./scripts/build/createUser.sh $user
done

/etc/init.d/mysqld start
. ./scripts/build/configureMySQL.sh intahwebz pass123 pass123


nginx
/etc/init.d/php-fpm start
/etc/init.d/redis start
/etc/init.d/supervisord start

echo "imagick.test 127.0.0.1" >> /etc/hosts

echo "you now need to put the clavis file in the right place"