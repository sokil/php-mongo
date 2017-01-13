#!/bin/bash

FILE=$(readlink -f $0);
DIR=$(dirname $FILE)

PHP_VERSION=$(php -r "echo phpversion();");
PHPUNIT_LOG_DIR=$DIR/../share/phpunit/${PHP_VERSION}

# prepare phpunit log dir
if [[ ! -d $PHPUNIT_LOG_DIR ]];
then
    mkdir -p $PHPUNIT_LOG_DIR
else
    rm -rf $ PHPUNIT_LOG_DIR/*.log
fi

# start bunch of tests
PHPMONGO_DSN=mongodb://mongodb24 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ${PHPUNIT_LOG_DIR}/mongo24.log
PHPMONGO_DSN=mongodb://mongodb26 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ${PHPUNIT_LOG_DIR}/mongo26.log
PHPMONGO_DSN=mongodb://mongodb30 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ${PHPUNIT_LOG_DIR}/mongo30.log
PHPMONGO_DSN=mongodb://mongodb32 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ${PHPUNIT_LOG_DIR}/mongo32.log
PHPMONGO_DSN=mongodb://mongodb33 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ${PHPUNIT_LOG_DIR}/mongo33.log
PHPMONGO_DSN=mongodb://mongodb34 ./vendor/bin/phpunit -c ./tests/phpunit.xml ./tests > ${PHPUNIT_LOG_DIR}/mongo34.log