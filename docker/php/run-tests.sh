#!/bin/bash

FILE=$(readlink -f $0);
PROJECT_DIR=$(dirname $FILE)/../..

PHP_VERSION=$(php -r "echo phpversion();");
PHPUNIT_LOG_DIR=$PROJECT_DIR/docker/share/phpunit/${PHP_VERSION}

# prepare phpunit log dir
if [[ ! -d $PHPUNIT_LOG_DIR ]];
then
    mkdir -p $PHPUNIT_LOG_DIR
else
    rm -rf $ PHPUNIT_LOG_DIR/*.log
fi

# start bunch of tests
PHPMONGO_DSN=mongodb://mongodb24 $PROJECT_DIR/vendor/bin/phpunit -c $PROJECT_DIR/tests/phpunit.xml $PROJECT_DIR/tests > ${PHPUNIT_LOG_DIR}/mongo24.log
PHPMONGO_DSN=mongodb://mongodb26 $PROJECT_DIR/vendor/bin/phpunit -c $PROJECT_DIR/tests/phpunit.xml $PROJECT_DIR/tests > ${PHPUNIT_LOG_DIR}/mongo26.log
PHPMONGO_DSN=mongodb://mongodb30 $PROJECT_DIR/vendor/bin/phpunit -c $PROJECT_DIR/tests/phpunit.xml $PROJECT_DIR/tests > ${PHPUNIT_LOG_DIR}/mongo30.log
PHPMONGO_DSN=mongodb://mongodb32 $PROJECT_DIR/vendor/bin/phpunit -c $PROJECT_DIR/tests/phpunit.xml $PROJECT_DIR/tests > ${PHPUNIT_LOG_DIR}/mongo32.log
PHPMONGO_DSN=mongodb://mongodb33 $PROJECT_DIR/vendor/bin/phpunit -c $PROJECT_DIR/tests/phpunit.xml $PROJECT_DIR/tests > ${PHPUNIT_LOG_DIR}/mongo33.log
PHPMONGO_DSN=mongodb://mongodb34 $PROJECT_DIR/vendor/bin/phpunit -c $PROJECT_DIR/tests/phpunit.xml $PROJECT_DIR/tests > ${PHPUNIT_LOG_DIR}/mongo34.log