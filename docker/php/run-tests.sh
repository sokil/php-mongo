#!/bin/bash

########################################################################################################################
# This script executed in container by calling from host machine/
# It run test on concrete PHP and MongoDB platforms
########################################################################################################################

# copy source files to prevent file modification
echo "Updating files in container..."
rsync -r /phpmongo-source/ /phpmongo/
echo "done."

PROJECT_DIR=$(dirname $(readlink -f $0))/../..

PHP_VERSION=$(php -r "echo phpversion();");

SHARE_DIR=/share/${PHP_VERSION:0:3}

PHPUNIT_LOG_DIR=/share/${PHP_VERSION}/phpunit

testPath=${PROJECT_DIR}/tests
testFilter=""

# prepare phpunit log dir
if [[ ! -d ${PHPUNIT_LOG_DIR} ]];
then
    mkdir -p ${PHPUNIT_LOG_DIR}
else
    rm -rf $ ${PHPUNIT_LOG_DIR}/*.log
fi

# get mongo versions from input arguments
while [[ $# -gt 1 ]]
do
    key="$1"
    value="$2"
    case $key in
        -m|--mongo)
            mongoVersion=$value
            shift
        ;;
        -t|--test)
            testPath="${testPath}/${value}"
            shift
        ;;
        -f|--filter)
            testFilter="--filter ${value}"
            shift
        ;;
        *)
        ;;
    esac
    shift
done

# start tests
PHPMONGO_DSN=mongodb://mongodb${mongoVersion} \
    $PROJECT_DIR/vendor/bin/phpunit \
    -c $PROJECT_DIR/tests/phpunit.xml \
    --colors=never \
    $testFilter \
    $testPath \
    | tee ${PHPUNIT_LOG_DIR}/mongo${mongoVersion}.log
