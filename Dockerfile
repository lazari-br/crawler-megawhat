FROM ubuntu:bionic

RUN addgroup --system nginx && \
     adduser --system --home /var/cache/nginx --shell /sbin/nologin nginx

# Install web components
RUN apt-get update && \
    apt-get install -y \
    locales \
    curl \
    wget \
    supervisor \
    nginx \
    htop \
    ldap-utils \
    php7.2-fpm \
    php7.2-gd \
    php7.2-curl \
    php7.2-mysql \
    php7.2-soap \
    php7.2-dom \
    php7.2-zip \
    php7.2-xml \
    php7.2-json \
    php7.2-ldap \
    php7.2-intl \
    php7.2-xsl \
    php7.2-mbstring \
    php7.2-opcache \
    php7.2-sqlite3 \
    php7.2-bcmath

RUN locale-gen pt_BR.UTF-8 && \
    update-locale LC_ALL=pt_BR.UTF-8 LC_CTYPE=pt_BR.UTF-8 LC_TIME=pt_BR.UTF-8 LANG=pt_BR.UTF-8 LANGUAGE=pt_BR.UTF-8 TZ=America/Sao_Paulo && \
    ln -fs /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime

# Install composer
RUN EXPECTED_COMPOSER_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig) && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '${EXPECTED_COMPOSER_SIGNATURE}') { echo 'Composer.phar Installer verified'; } else { echo 'Composer.phar Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --install-dir=/usr/bin --filename=composer && \
    php -r "unlink('composer-setup.php');"

# PHP-FPM
ADD docker/conf/php.ini /etc/php/7.2/fpm/php.ini
ADD docker/conf/www.conf /etc/php/7.2/fpm/pool.d/www.conf

# NGINX config files
ADD docker/conf/nginx.conf /etc/nginx/nginx.conf
ADD docker/conf/nginx-site.conf /etc/nginx/sites-available/default.conf
RUN rm /etc/nginx/sites-enabled/default && \
    ln -s /etc/nginx/sites-available/default.conf /etc/nginx/sites-enabled/default && \
    mkdir -p /run/php

ADD docker/conf/supervisord.conf /etc/supervisord.conf

# Copy start.sh
ADD docker/scripts/start.sh /usr/bin/start.sh

# Setup directories
RUN chmod 755 /usr/bin/start.sh && \
    rm -Rf /var/www/*

# Copy application
ADD . /var/www/html/

# Expose port
EXPOSE 80

# Set the workdir
WORKDIR /var/www/html

# Start the container
CMD ["start.sh"]