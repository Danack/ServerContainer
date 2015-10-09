
# set -eux -o pipefail
set -x

#grep status is 0 if a line is selected, 1 if no lines were selected, and 2 if an error occurred.
  
# wget -O /tmp/basereality-GPG-KEY.public http://rpm.basereality.com/basereality-GPG-KEY.public && '

rpm -qai "*gpg*" | grep -q basereality

if [ $? -ne 0 ]; 
    then
        rpm --import ./data/basereality-GPG-KEY.public
        echo "adding basereality public key"
    else
        echo "basereality public key already added"
fi
