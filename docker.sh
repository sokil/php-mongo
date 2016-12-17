#!/bin/bash

# To start XDebug debugging set env variable PHPMONGO_DEBUG

cd /phpmongo/

### Register host machine
export DOCKERHOST_IP="$(/sbin/ip route|awk '/default/ { print $3 }')";
echo "$DOCKERHOST_IP dockerhost" >> /etc/hosts

# install php extensions
if [[ -z $(dpkg -l | grep libssl-dev) ]];
then
    # add library requirements
    apt-get update
    apt-get install --no-install-recommends -y libssl-dev

    # install pecl mongo
    yes '' | pecl install mongo-1.6.2
    docker-php-ext-enable mongo.so

    # install ext-zip
    docker-php-ext-install zip
    
    # XDEBUG
    if [[ $PHPMONGO_DEBUG ]];
    then
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
fi

# print versions
php -r "echo 'PECL Mongo client: ' . \MongoClient::VERSION . PHP_EOL;"

# install composer
if [[  -z $(which composer) ]];
then
    # download composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
    # update composer dependencies
    composer update --no-interaction
fi

# run tests
if [[ ! -d ./share/phpunit ]];
then
    mkdir -p ./share/phpunit
else
    rm -rf ./share/phpunit/*.log
fi

# bootstrap
if [[ $PHPMONGO_DEBUG ]];
then
    # debug or run tests manually
    echo "Debugging session initialised"
    php -S 127.0.0.1:9876 .
else
    echo "Start Phpunit tests"
    # uncomment to run tests automatically
    PHPMONGO_DSN=mongodb://mongodb24 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/mongo24.log
    PHPMONGO_DSN=mongodb://mongodb26 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/mongo26.log
    PHPMONGO_DSN=mongodb://mongodb30 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/mongo30.log
    PHPMONGO_DSN=mongodb://mongodb32 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/mongo32.log
    PHPMONGO_DSN=mongodb://mongodb33 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/mongo33.log
    PHPMONGO_DSN=mongodb://mongodb34 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ./share/phpunit/mongo34.log
fi


