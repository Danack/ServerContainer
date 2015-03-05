
set -x #echo on

if [ "$#" -ne 4 ]; then
    echo "Illegal number of parameters ${numParams}, should be deployPackage projectName sha zipFilename archiveName"
    exit -1
fi

projectName=$1
sha=$2
zipName=$3
archiveName=$4

sh ./scripts/build/createUser.sh $projectName

projectRootDir="/home/${projectName}"
targetDir="${projectRootDir}/${sha}"
linkCurrentDir="${projectRootDir}/current"

ln -s /home/servercontainer/clavis.php ${projectRootDir}/clavis.php


mkdir -p $targetDir
tar -xf ${zipName} -C $targetDir --strip-components=1

cd $targetDir

cwd=$(pwd)
echo  "Now in: ${cwd}"

chown -R ${projectName}:www-data $targetDir

sh ./scripts/deploy.sh centos

ln -sfn $targetDir $linkCurrentDir

/etc/init.d/supervisord restart
/etc/init.d/php-fpm restart
nginx -s reload
