
# set -eux -o pipefail
set -x

yum install wget git cloud-init

cat << EOF > ./basereality.repo

[basereality]
name=BaseReality packages
baseurl=http://rpm.basereality.com/RPMS/$basearch
enabled=1
gpgcheck=1

[basereality-noarch]
name=BaseReality noarch packages
baseurl=http://rpm.basereality.com/RPMS/noarch
enabled=1
gpgcheck=1

EOF


cat << EOF > ./basereality-GPG-KEY.public
-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG v2.0.14 (GNU/Linux)

mQENBFRT0AABCAC/S7Xs4kG2G2Us+o9kdpLiGKrYgk2zyWYi6UoqfCaZiiaR7sPd
YmNnFy+Alj3H0nDrBGC3p2z5gtWn2A2PodLLr4AVSuwPXjKCnmI4wr6FepBABXSN
CqptJv3USKUd+a47xgKCRr9P5duiEhred+E7jzdOsuBO3gedwdJU6DMMCm6TbBY9
RiyZDd+/yCbfL4ZVH6ytvdDPtVOgVxfdBv6IhPs6f96afjs6mQelThIc4XOBRnAh
H4tv7nNjKA+ZJ/CxSekXoYz0ZjmXTomjaI3ebYZLKteEtQDSovkWhYnHs9Zn46HO
WQNP9RgqQwDAjlIH36RfVzxrFybLu5JyuuNLABEBAAG0LkRhbiBBY2tyb3lkIChC
YXN0aW9uKSA8RGFuYWNrQGJhc2VyZWFsaXR5LmNvbT6JATgEEwECACIFAlRT0AAC
GwMGCwkIBwMCBhUIAgkKCwQWAgMBAh4BAheAAAoJEEQtKMJWWd4JlO0IAKAnwXgF
nMqTtNXLygDXbNBdiZ0c482Tf2YneuB0XUHIYfigtl+wAk+z8oFSO3yXGT6uCAkL
pyFvFk3suRYgqY0Qs06owkxKi0fk197ukZUWAChYjh+0ylaXc225igbm2WAvmBwH
FW1Gpnr7mMYHtMqWsjAid+gXMnQz+GExZkXtRYXiqRRJqKWhS3WC0teUBm5let8Y
qcWiGG42SHCXsPNJBiDvu9lp6QA/aL+R1QesWmtUnCNSrI7PmZYn1qTEFxZvhALz
VuUEL11V4o5lM30y/z+/LIeXEmy57a8TiwXUb82TQZXLD+aaOygqN2Bd4CS776iB
DUr37V+p6GV2ul4=
=tJWF
-----END PGP PUBLIC KEY BLOCK-----

EOF


rpmPath="/etc/yum.repos.d/basereality.repo"

if [ ! -f $rpmPath ]; then
    echo "basereality.repo not found in /etc/yum.repos.d, adding it."
    cp ./basereality.repo $rpmPath
    chmod 00644 $rpmPath
    chown root:root $rpmPath
fi


rpm -qai "*gpg*" | grep -q basereality

if [ $? -ne 0 ]; 
    then
        rpm --import ./basereality-GPG-KEY.public
        echo "adding basereality public key"
    else
        echo "basereality public key already added"
fi





#   /etc/ssh/sshd_config
# Prevent root logins:
#PermitRootLogin no

# Port 2345  #Change me

# Disable password authentication forcing use of keys
# PasswordAuthentication no

# service sshd restart


user="hoboken"
mkdir -p /home/${user}/.ssh
cp ~/.ssh/authorized_keys /home/${user}/.ssh/authorized_keys
chown web /home/${user}/.ssh
chgrp web /home/${user}/.ssh
chown -R :${user} /home/${user}/.ssh
# find /home/web/.ssh -type d -exec chmod g+s '{}' \;
chmod 0700 /home/${user}/.ssh
chmod 0600 /home/${user}/.ssh/*
service sshd restart


chmod u=rwx,go= .ssh    # 0700
chmod u=rw,go= .ssh/*   # 0600