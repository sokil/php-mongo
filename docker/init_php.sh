#!/bin/bash

# To start XDebug debugging set env variable PHPMONGO_DEBUG

cd /phpmongo/

#####################################
#        Console arguments          #
#####################################
MONGO_EXT=$1
if [[ -z $MONGO_EXT ]];
then
    MONGO_EXT="mongo"
fi;

MONGO_EXT_VERSION=$2
if [[ -z $MONGO_EXT_VERSION ]];
then
    MONGO_EXT_VERSION="1.6.2"
fi;

#####################################
#        Environment variables      #
#####################################

# Register host machine
export DOCKERHOST_IP="$(/sbin/ip route|awk '/default/ { print $3 }')";
echo "$DOCKERHOST_IP dockerhost" >> /etc/hosts

### Set env vars
export PHP_VERSION=$(php -r "echo phpversion();");

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
#        Start environment          #
#####################################

if [[ $PHPMONGO_DEBUG ]];
then
    # debug or run tests manually
    echo "Debugging session initialised. To enter container's console, type:"
    echo -e "\033[1;37mPHP 5.6: \033[0m docker exec -it phpmongo_php56 bash"
    echo -e "\033[1;37mPHP 7.0: \033[0m docker exec -it phpmongo_php70 bash"
    echo -e "\033[1;37mPHP 7.1: \033[0m docker exec -it phpmongo_php71 bash"

    php -S 127.0.0.1:9876 .
else
    # run test automatically
    echo "Start Phpunit tests"

    # prepare phpunit log dir
    if [[ ! -d ./share/phpunit ]];
    then
        mkdir -p ./share/phpunit
    else
        rm -rf ./share/phpunit/*.log
    fi

    # start bunch of tests
    PHPMONGO_DSN=mongodb://mongodb24 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/${PHP_VERSION}-mongo24.log
    PHPMONGO_DSN=mongodb://mongodb26 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/${PHP_VERSION}-mongo26.log
    PHPMONGO_DSN=mongodb://mongodb30 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/${PHP_VERSION}-mongo30.log
    PHPMONGO_DSN=mongodb://mongodb32 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/${PHP_VERSION}-mongo32.log
    PHPMONGO_DSN=mongodb://mongodb33 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/${PHP_VERSION}-mongo33.log
    PHPMONGO_DSN=mongodb://mongodb34 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/${PHP_VERSION}-mongo34.log
fi


