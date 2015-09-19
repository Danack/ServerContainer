
# set -eux -o pipefail

set -eux

#set -x

if [ "$#" -ne 5 ]; then
    echo "Illegal number of parameters, should be deployPackage projectName sha zipFilename archiveName environment"
    exit -1
fi

projectName=$1
sha=$2
zipName=$3
archiveName=$4
envSetting=$5

sh ./scripts/build/createUser.sh $projectName

projectRootDir="/home/${projectName}"
targetDir="${projectRootDir}/${sha}"

mkdir -p $targetDir
tar -xf ${zipName} -C $targetDir --strip-components=1

php bin/cli.php writeClavisFile \
    ${projectName} \
    ${envSetting} \
    ${projectRootDir}/clavis.php \
    ${targetDir}/data/keysRequired.json


cd $targetDir

cwd=$(pwd)
echo  "Now in: ${cwd}"

echo "<?php" > releaseInfo.php
echo "" >> releaseInfo.php
echo "" >> releaseInfo.php
echo "\$default = [" >> releaseInfo.php
echo "    'release.version' => '${sha}'" >> releaseInfo.php 
echo "];" >> releaseInfo.php
echo "" >> releaseInfo.php

chown -R ${projectName}:www-data $targetDir

sh ./scripts/deploy.sh ${envSetting}

/etc/init.d/supervisord restart
/etc/init.d/php-fpm restart
nginx -s reload
