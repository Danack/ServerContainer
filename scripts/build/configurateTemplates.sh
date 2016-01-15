

environment="centos,live"

if [ "$#" -ge 1 ]; then
    environment=$1
fi

echo "environment is ${environment}";

mkdir autogen

php vendor/bin/configurate -p data/config.php data/config_template/my.cnf.php autogen/my.cnf $environment
php vendor/bin/configurate -p data/config.php data/config_template/nginx.conf.php autogen/nginx.conf $environment
php vendor/bin/configurate -p data/config.php data/config_template/php-fpm.conf.php autogen/php-fpm.conf $environment
php vendor/bin/configurate -p data/config.php data/config_template/php.ini.php autogen/php.ini $environment

php vendor/bin/configurate -p data/config.php data/config_template/addConfig.sh.php autogen/addConfig.sh $environment

#convert the php ini file to php-fpm format
vendor/bin/fpmconv autogen/php.ini autogen/php.fpm.ini 

bash autogen/addConfig.sh