rpmPath="/etc/yum.repos.d/basereality.repo"

if [ ! -f $rpmPath ]; then
    echo "basereality.repo not found in /etc/yum.repos.d, adding it."
    cp ../data/basereality.repo $rpmPath
    chmod 00644 $rpmPath
    chown root:root $rpmPath
fi
