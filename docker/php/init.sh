#!/bin/bash

#####################################
#        Environment                #
#####################################

# Project dir
PROJECT_DIR="/phpmongo/"

### PHP version
PHP_VERSION=$(php -r "echo phpversion();");

# Existence of this dir points that container initialised
SHARE_DIR=/share/${PHP_VERSION:0:3}

# Remove share dir.
rm -rf ${SHARE_DIR}

# Mongo extension version
MONGO_EXT_VERSION=$1
if [[ -z $MONGO_EXT_VERSION ]];
then
    echo "Mongo extension version not specified"
    exit
fi;

# mongo version notification
echo "Creating environment for MongoDB PHP extension 'mongodb' ver. ${MONGO_EXT_VERSION} and PHP ${PHP_VERSION}"

#####################################
#        PHP extensions             #
#####################################

if [[ -z $(dpkg -l | grep libssl-dev) ]];
then
    # add library requirements
    apt-get update -q
    apt-get install --no-install-recommends -y libssl-dev iproute2 rsync

    # install ext-zip
    apt-get install --no-install-recommends -y zlib1g-dev libzip-dev
    docker-php-ext-install zip

    # update pecl
    pecl channel-update pecl.php.net

    # install pecl mongo extension
    yes '' | pecl -q install -f mongodb-${MONGO_EXT_VERSION}
    docker-php-ext-enable mongodb.so

    # Docker host for XDEBUG
    DOCKERHOST_IP="$(/sbin/ip route | awk '/default/ { print $3 }')";
    echo "$DOCKERHOST_IP dockerhost" >> /etc/hosts

    # last version of xdebug with support PHP < 7.0 is 2.5.5
    if [[ ${PHP_VERSION:0:2} == "5." ]];
    then
        pecl install xdebug-2.5.5;
    else
        pecl install xdebug;
    fi

    docker-php-ext-enable xdebug.so

    echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_mode=req" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_port=9001" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_host=dockerhost" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.extended_info=1" >> /usr/local/etc/php/conf.d/xdebug.ini
fi

#####################################
#        prepare project            #
#####################################
# Copy files to prevent file modification
echo 'Copying files to container ... '
rsync -r /phpmongo-source/ ${PROJECT_DIR}
echo 'done.'

#####################################
#        Composer                   #
#####################################

if [[ -z $(which composer) ]];
then
    # go to project dir
    cd $PROJECT_DIR

    # download composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

    composer update --no-interaction -o

    # add mongodb compatibility layer
    echo "Installing compatibility layer for new MongoDB extension"
    composer require "alcaeus/mongo-php-adapter" --ignore-platform-reqs --no-interaction -o
fi

# this dir used as marker of container initialisation
mkdir ${SHARE_DIR}

# start process to prevent stop of container
echo "Container for PHP ${PHP_VERSION} initialised."
php -S 127.0.0.1:9876 .
