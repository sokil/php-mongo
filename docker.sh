#!/bin/bash

# install php extensions
if [[ -z $(dpkg -l | grep libssl-dev) ]];
then
    # add library requirements
    apt-get update
    apt-get install --no-install-recommends -y libssl-dev

    # install pecl mongo
    yes '' | pecl install mongo
    docker-php-ext-enable mongo.so
    php -r "echo \MongoClient::VERSION . PHP_EOL;"

    # install ext-zip
    docker-php-ext-install zip
    
    # xdebug
    # pecl install xdebug
    # docker-php-ext-enable xdebug.so
    # echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini
    # echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
    
    # add env var
    # XDEBUG_CONFIG="idekey=PHPSTORM remote_host={PHPSTORM_HOST_IP} remote_port={PHPSTROM_XDEBUG_PORT}"

fi

# install composer
if [[  -z $(which composer) ]];
then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
fi

# update composer dependencies
cd /phpmongo/
composer update

# run tests
if [[ ! -d ./log/docker_tests ]];
then
    mkdir -p ./log/docker_tests
else
    rm -rf ./log/docker_tests/*.log
fi

PHPMONGO_DSN=mongodb://mongodb26 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./log/docker_tests/mongo26.log
PHPMONGO_DSN=mongodb://mongodb30 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./log/docker_tests/mongo30.log
PHPMONGO_DSN=mongodb://mongodb32 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./log/docker_tests/mongo32.log
PHPMONGO_DSN=mongodb://mongodb33 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./log/docker_tests/mongo33.log

