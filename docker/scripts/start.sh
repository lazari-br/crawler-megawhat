#!/bin/bash

echo "Setting variables"
bash -x /tmp/add-env.sh

#php artisan migrate --force
#php artisan storage:link -q -n
#php artisan view:clear
#php artisan config:clear

#find /var/www/html/storage \! -user nginx -exec chown nginx:nginx {} \;
exec /usr/bin/supervisord -n -c /etc/supervisord.conf
#tries=${QUEUE_TRIES:-"1"}
#timeout=${QUEUE_TIMEOUT:-"60"}

#php artisan queue:work --queue=$QUEUE_NAME --tries=$tries --timeout=$timeout

