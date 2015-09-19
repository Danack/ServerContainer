
set -eux -o pipefail
# set -x

packages=()
#packages+=("composer-2015_01_24")
packages+=("composer-2015_02_28")
packages+=("fftw")
packages+=("ghostscript")
packages+=("java-1.6.0-openjdk")
packages+=("libjpeg-turbo")
packages+=("libpng")
packages+=("ImageMagick-2015_06_22")
packages+=("imagick-2015_09_01")
packages+=("php-basereality-2015_09_05-5.6.9")


packages+=("mysql-community-client");
packages+=("mysql-community-server");
packages+=("libwebp-0.4.2")
packages+=("nginx-basereality-2015_01_25")
packages+=("redis-basereality-2015_01_07")

#setuptools is needed by supervisor...but isn't insalled automatically by yum :-(
packages+=("setuptools")
packages+=("strace")
packages+=("supervisor")
packages+=("yuicompressor")

#this is an implode
packageString=$( IFS=$' '; echo "${packages[*]}" )

echo "Installing packages $packageString"

yum erase python-setuptools-0.6.10-3.el6.noarch

yum -y install $packageString



