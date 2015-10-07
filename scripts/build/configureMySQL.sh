#!/bin/bash

# set -eux -o pipefail


if [ -f /home/servercontainer/mysql_deployed.txt ]
  then
    echo "/home/servercontainer/mysql_deployed.txt exists."
    return
fi

SQL_SITE_USERNAME=$1
SQL_SITE_PASSWORD=$2
SQL_ROOT_PASSWORD=$3

echo "SQL_SITE_USERNAME " + $SQL_SITE_USERNAME
echo "SQL_SITE_PASSWORD " + $SQL_SITE_PASSWORD
echo "SQL_ROOT_PASSWORD " + $SQL_ROOT_PASSWORD

################################
#SQL
################################

echo "configureMySQL start."

#remove every user except for the root on local host.
mysql -uroot -e "delete from mysql.user where ( user not like 'root' or host in ('127.0.0.1', '::1', 'localhost') is false);"

echo "SQL_SITE_USERNAME is ${SQL_SITE_USERNAME}"
echo "SQL_SITE_PASSWORD is ${SQL_SITE_PASSWORD}"
echo "SQL_ROOT_PASSWORD is ${SQL_ROOT_PASSWORD}"

locals=( "localhost" "127.0.0.1" "::1" )

for local in "${locals[@]}"
do
   :
mysql -uroot -e "CREATE USER '${SQL_SITE_USERNAME}'@'${local}' IDENTIFIED BY '${SQL_SITE_PASSWORD}';"
mysql -uroot -e "GRANT PROCESS, USAGE ON *.* TO '${SQL_SITE_USERNAME}'@'${local}';"
mysql -uroot -e "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER ON basereality.* TO '${SQL_SITE_USERNAME}'@'${local}';"

done

# Set the root user password for all local domains
echo "Setting root password"
mysql -uroot -e "UPDATE mysql.user SET Password=PASSWORD('${SQL_ROOT_PASSWORD}') WHERE User='root';"

echo "Flushing privileges"
mysql -uroot -e "FLUSH PRIVILEGES;"


echo "MySQL is deployed" > /home/servercontainer/mysql_deployed.txt

echo "configureMySQL end."