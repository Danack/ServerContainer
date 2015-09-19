set -eux -o pipefail
#set -ex

oauthtoken=`php bin/info.php "github.access_token"`
composer config -g github-oauth.github.com $oauthtoken

composerpath=`which composer`
php -d allow_url_fopen=1 $composerpath install

mkdir -p autogen

php vendor/bin/configurate -p data/config.php data/my.cnf.php autogen/my.cnf.conf amazonec2
php vendor/bin/configurate -p data/config.php data/addConfig.sh.php autogen/addConfig.sh amazonec2
