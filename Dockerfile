# This is a super-hacky Dockerfile that runs a basic brukerinfo installation in
# Apache
#
# Note:
#   Prior to build, you'll have to run composer in the repo root directory to
#   fecth all dependencies.
#   This adds an extra step, but also makes it easier to much around with the
#   autoloader to e.g. use a local copy of 'phplib'.
#
# Get started:
#   composer install --ignore-platform-reqs
#   docker build --rm -f Dockerfile -t brukerinfo .
#   docker run --rm -it \
#       -p 8080:80 \
#       -e WOFH_BOFH_URL=http://host:port/
#
# If you don't want to rebuild on changes:
#   docker run --rm -it -p 8080:80 \
#       -p 8080:80 \
#       -e WOFH_BOFH_URL=http://host:port/
#       -v "$(pwd):/usr/local/src/wofh"
#       -v "$(pwd)/my-config.php:/usr/local/src/wofh.php"
#
FROM php:5-apache

# Get required php exts
RUN apt-get update \
    && apt-get install -y libxml2-dev libexpat-dev  \
    && docker-php-ext-install -j$(nproc) xmlrpc
# TODO: Do we need mcrypt? The documentation says so... Maybe xmlrpc use it when
# SSL is enabled?
# RUN apt-get update \
#     && apt-get install -y libmcrypt-dev \
#     && docker-php-ext-install -j$(nproc) mcrypt

# Enable mod_rewrite
RUN a2enmod rewrite

WORKDIR /usr/local/src

ENV \
    APACHE_DOCUMENT_ROOT=/usr/local/src/wofh/www_docs \
    WOFH_CONFIG=/usr/local/src/wofh.php \
    WOFH_INST=uio \
    WOFH_BOFH_URL=http://localhost:8000/

# Remove the default site
RUN rm -f /etc/apache2/sites-enabled/*.conf \
    && ln -s ../sites-available/wofh.conf /etc/apache2/sites-enabled/wofh.conf


# TODO: Make a full apache config, so we don't have to
# COPY ./docker/apache-site.conf /etc/apache2/sites-available/000-default.conf
COPY ./docker/apache-site.conf /etc/apache2/sites-available/wofh.conf
COPY ./docker/php.ini /usr/local/etc/php/
COPY ./docker/config.php ${WOFH_CONFIG}
COPY . /usr/local/src/wofh/
