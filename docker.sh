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
PHPMONGO_DSN=mongodb://mongodb26 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests
PHPMONGO_DSN=mongodb://mongodb33 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests

