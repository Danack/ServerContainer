
packages=( )


packages+=("fftw")
packages+=("ghostscript")
packages+=("java-1.6.0-openjdk")
packages+=("libjpeg-turbo")
packages+=("libpng")
packages+=("ImageMagick-2015_01_20")
packages+=("imagick-2015_01_20")
packages+=("libwebp-0.4.2")
packages+=("nginx-basereality-2014_11_05")
packages+=("php-basereality-2014_10_24")
packages+=("redis-basereality-2014_11_05")
packages+=("strace")
packages+=("supervisor")
packages+=("yuicompressor")


#this is an implode
packageString=$( IFS=$' '; echo "${packages[*]}" )

echo "Installing packages $packageString"

yum -y install $packageString