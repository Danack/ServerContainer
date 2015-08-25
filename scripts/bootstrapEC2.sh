
set -eux -o pipefail

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

usermod -a -G www-data nginx
