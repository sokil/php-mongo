#!/bin/bash

#####################################
#        Environment                #
#####################################

# Project dir
PROJECT_DIR="/phpmongo/"

# Mongo extension
MONGO_EXT=$1
if [[ -z $MONGO_EXT ]];
then
    MONGO_EXT="mongo"
fi;

# Mongo extension version
MONGO_EXT_VERSION=$2
if [[ -z $MONGO_EXT_VERSION ]];
then
    MONGO_EXT_VERSION="1.6.2"
fi;

# Host machine
DOCKERHOST_IP="$(/sbin/ip route|awk '/default/ { print $3 }')";
echo "$DOCKERHOST_IP dockerhost" >> /etc/hosts

### PHP version
PHP_VERSION=$(php -r "echo phpversion();");

#####################################
#        PHP extensions             #
#####################################

if [[ -z $(dpkg -l | grep libssl-dev) ]];
then
    # add library requirements
    apt-get update -q
    apt-get install --no-install-recommends -y libssl-dev

    # install extensions
    docker-php-ext-install zip

    # install pecl mongo extension
    yes '' | pecl -q install -f ${MONGO_EXT}-${MONGO_EXT_VERSION}
    docker-php-ext-enable ${MONGO_EXT}.so

    # XDEBUG
    pecl install xdebug
    docker-php-ext-enable xdebug.so

    echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_mode=req" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_port=9001" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_host=dockerhost" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/xdebug.ini
fi

#####################################
#        Composer                   #
#####################################

if [[  -z $(which composer) ]];
then
    # download composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
    # add mongodb compatibility layer
    if [[ $MONGO_EXT = "mongodb" ]];
    then
        composer require "alcaeus/mongo-php-adapter" --ignore-platform-reqs
    fi;
    # update composer dependencies
    composer update --no-interaction
fi

#####################################
#        Start dev environment      #
#####################################

# debug or run tests manually
echo "Debugging session for ${PHP_VERSION} initialised."

php -S 127.0.0.1:9876 .
