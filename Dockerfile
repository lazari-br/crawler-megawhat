FROM doc88/clt-php-nginx

# PHP-FPM
ADD docker/conf/php.ini /etc/php/7.2/fpm/php.ini
ADD docker/conf/www.conf /etc/php/7.2/fpm/pool.d/www.conf
ADD docker/conf/php-fpm.conf /usr/local/etc/php-fpm.conf

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
RUN mkdir -p /var/www/html/storage/framework/sessions; mkdir -p /var/www/html/storage/framework/views; mkdir -p /var/www/html/storage/framework/cache;  mkdir -p /var/www/html/storage/app/public

# Adjust .env
RUN touch /var/www/html/.env
COPY docker/scripts/add-env.sh /tmp/add-env.sh
RUN chmod 777 /tmp/add-env.sh

# Expose port
EXPOSE 80

# Set the workdir
WORKDIR /var/www/html

# Start the container
CMD ["start.sh"]
