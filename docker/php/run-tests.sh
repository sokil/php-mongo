#!/bin/bash

########################################################################################################################
# This script executed in container by calling from host machine/
# It run test on concrete PHP and MongoDB platforms
########################################################################################################################

PROJECT_DIR=$(dirname $(readlink -f $0))/../..

PHPVersion=$(php -r "echo phpversion();");
PHPUnitLogDir=${PROJECT_DIR}/share/phpunit/${PHPVersion}
testPath=${PROJECT_DIR}/tests
testFilter=""

# prepare phpunit log dir
if [[ ! -d $PHPUnitLogDir ]];
then
    mkdir -p $PHPUnitLogDir
else
    rm -rf $ $PHPUnitLogDir/*.log
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
    | tee $PHPUnitLogDir/mongo${mongoVersion}.log
