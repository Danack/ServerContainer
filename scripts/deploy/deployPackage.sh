
if [ "$#" -ne 5 ]; then
    echo "Illegal number of parameters ${numParams}, should be deployPackage packagename sha"
    exit -1
fi

packageName=$1
filename=$2
deployScriptname=$3
outputDirname=$4
usename=$5


#echo "packageName is ${packageName}"
#echo "filename is ${filename}"
#echo "deployScriptname is ${deployScriptname}"
#echo "outputDirname is ${outputDirname}"
#echo "usename is ${usename}"

targetDir="/home/${packageName}/"
outputDir="${targetDir}/"

mkdir -p $targetDir
#echo "targetDir is ${targetDir}"
tar -xf ${filename} -C $targetDir

deployScriptDir="${targetDir}/${outputDirname}"

cd $deployScriptDir
echo  "Now in: "
pwd

sh  ./${deployScriptname}


# echo "intahwebz" | passwd --stdin $createWebUser
#nginx requires the directory tree to be executable
#chmod +x /home/intahwebz/


