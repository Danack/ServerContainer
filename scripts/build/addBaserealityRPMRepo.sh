# set -eux -o pipefail

rpmPath="/etc/yum.repos.d/basereality.repo"

if [ ! -f $rpmPath ]; then
    echo "basereality.repo not found in /etc/yum.repos.d, adding it."
    cp ../data/basereality.repo $rpmPath
    chmod 00644 $rpmPath
    chown root:root $rpmPath
fi



proxyPath="/etc/yum.repos.d/yum.proxy.conf"


if [ ! -f $rpmPath ]; then
    echo "yum.proxy.conf not found in /etc/yum.repos.d, adding it."
    cp ../data/yum.proxy.conf $proxyPath
    chmod 00644 $proxyPath
    chown root:root $proxyPath
fi



#group="www-data"

#egrep -i -q "^proxy" /etc/yum.conf

#if [ $? -eq 0 ]; then
#   echo "Group ${group} exists"
#else
#   echo "Adding proxy for yum, creating."
#   echo "proxy=http://10.0.2.2:3128" >> /etc/yum.conf
#fi


