# source environment variables to use php 7.3
source scl_source enable rh-php73

cd '/var/www/nmsprime'
/opt/rh/rh-php73/root/usr/bin/php artisan nms:radgroupreply-repopulate