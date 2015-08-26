
# set -eux -o pipefail

packages=()
#packages+=("composer-2015_01_24")
packages+=("composer-2015_02_28")
packages+=("fftw")
packages+=("ghostscript")
packages+=("java-1.6.0-openjdk")
packages+=("libjpeg-turbo")
packages+=("libpng")
#packages+=("ImageMagick-2015_01_20")
#packages+=("imagick-2015_02_25")
packages+=("ImageMagick-2015_06_22")
packages+=("imagick-2015_06_22")
packages+=("php-basereality-2015_02_01-5.6.2")
#packages+=("php-basereality-2015_02_15-5.6.5")
#packages+=("php-basereality-2015_03_20-5.6.7")
#packages+=("imagick7-2015_01_30")
#packages+=("php7-basereality-2015_01_30")
#packages+=("mysql");
#packages+=("mysql-server");
packages+=("mysql-community-client");
packages+=("mysql-community-server");
packages+=("libwebp-0.4.2")
packages+=("nginx-basereality-2015_01_25")
packages+=("redis-basereality-2015_01_07")
packages+=("setuptools")
packages+=("strace")
packages+=("supervisor")
packages+=("yuicompressor")

#this is an implode
packageString=$( IFS=$' '; echo "${packages[*]}" )

echo "Installing packages $packageString"

# yum erase python-setuptools-0.6.10-3.el6.noarch

yum -y install $packageString



