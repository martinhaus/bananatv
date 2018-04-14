FROM wordpress

ARG DEBIAN_FRONTEND=noninteractive

# install apt-utils
RUN apt-get update && apt-get install -y --no-install-recommends apt-utils

# install imagick
RUN apt-get update && apt-get install -y --no-install-recommends libmagickwand-6.q16-dev --no-install-recommends \
&& ln -s /usr/lib/x86_64-linux-gnu/ImageMagick-6.8.9/bin-Q16/MagickWand-config /usr/bin \
&& pecl install imagick \
&& echo "extension=imagick.so" > /usr/local/etc/php/conf.d/ext-imagick.ini

# install ghostscript, neccessary for PDFs in Imagick
RUN apt-get update && apt-get install -y ghostscript --no-install-recommends && \
ln -s /usr/local/bin/gs /usr/bin/gs

# install curl
RUN apt-get update && \
apt-get install -y --no-install-recommends libssl-dev libcurl4-openssl-dev pkg-config \
&& docker-php-ext-install curl

#install curl
RUN apt-get update && \
apt-get install -y --no-install-recommends libzip-dev zip \
&& docker-php-ext-configure zip --with-libzip \
&& docker-php-ext-install zip

