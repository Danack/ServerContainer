#!/bin/bash

# On a Amazon linux image we can use this to log console information to a file
# this doesn't appear to work on the Centos image we're using.
#delete this exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/tty0) 2>&1
# exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

intahwebzGroup="www-data"

cd /tmp

wget -O master.tgz https://github.com/Danack/ServerContainer/archive/master.tar.gz

tar -xvf master.tgz

cd ./ServerContainer-master/scripts

. ./build/createGroup.sh

users=()
#users+=("imagickdemos")
#users+=("intahwebz")
users+=("servercontainer")

for user in "${users[@]}"
do
   :
   . ./build/createUser.sh $user
done

#mkdir -p /home/servercontainer

cp -R /tmp/ServerContainer-master/* /home/servercontainer

find /home/servercontainer -type d -exec chmod 755 {} \;
find /home/servercontainer -type f -exec chmod 755 {} \;

chown -R servercontainer:www-data /home/servercontainer

#Nginx requires directory to be executable.
chmod +x /home/servercontainer

. ./build/aliasPHP.sh
. ./build/importBaserealityGPGPublicKey.sh
. ./build/addBaserealityRPMRepo.sh
. ./build/installPackages.sh
. ./build/configureIPTables.sh

cd /home/servercontainer
su servercontainer -c "/usr/local/bin/php -d allow_url_fopen=1 lib/composer install"

if [ $? -ne 0 ];then
  errorCode=$?
  echo "Composer install failed"
  exit $errorCode
fi


usermod -a -G www-data nginx

configFile="/home/clavis.php"

echo "<?php" > $configFile
echo "" >> $configFile
echo "define('FLICKR_KEY', '%FLICKR_KEY%');" >> $configFile
echo "define('FLICKR_SECRET', '%FLICKR_SECRET%');" >> $configFile
echo "" >> $configFile
echo "define('GITHUB_ACCESS_TOKEN', '%GITHUB_ACCESS_TOKEN%');" >> $configFile
echo "define('GITHUB_REPO_NAME', 'Danack/ServerContainer');" >> $configFile
echo "" >> $configFile
echo "define('AWS_SERVICES_KEY', '%AWS_SERVICES_KEY%');" >> $configFile
echo "define('AWS_SERVICES_SECRET', '%AWS_SERVICES_SECRET%');" >> $configFile
echo "" >> $configFile
