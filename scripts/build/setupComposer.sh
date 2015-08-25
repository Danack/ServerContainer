# set -eux -o pipefail

#grep status is 0 if a line is selected, 1 if no lines were selected, and 2 if an error occurred. 

#cat /root/.bashrc | grep -q "/usr/local/bin/php"
#if [ $? -ne 0 ];
#    then 
#        echo "alias php='/usr/local/bin/php'" >> /root/.bashrc
#        echo "aliased php to /usr/local/bin/php"
#    else 
#        echo "alias for php already present"
#fi

#cat /root/.bashrc | grep -q "/usr/local/bin/php-config"
#if [ $? -ne 0 ];
#    then 
#        echo "alias php-config='/usr/local/bin/php-config'" >> /root/.bashrc
#        echo "aliased php-config"
#    else 
#        echo "alias for php-config already present"
#fi

composerPath="/usr/sbin/composer"
cat /root/.bashrc | grep -q $composerPath
if [ $? -ne 0 ];
    then 
        echo "alias composer='${composerPath}'" >> /root/.bashrc
        echo "aliased composer"
    else 
        echo "alias for composer already present"
fi

