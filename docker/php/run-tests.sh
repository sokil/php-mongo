#!/bin/bash

########################################################################################################################
# This script executed in container by calling from host machine, 
# and run test on concrete PHP platform and all specified MongoDB platforms
########################################################################################################################

file=$(readlink -f $0);
projectDir=$(dirname $file)/../..

PHPVersion=$(php -r "echo phpversion();");
PHPUnitLogDir=$projectDir/share/phpunit/${PHPVersion}
testPath=$projectDir/tests
testFilter=""

# prepare phpunit log dir
if [[ ! -d $PHPUnitLogDir ]];
then
    mkdir -p $PHPUnitLogDir
else
    rm -rf $ $PHPUnitLogDir/*.log
fi

# init mongo versions
mongoVersions=()
mongoVersionsCount=0

# get mongo versions from input arguments
while [[ $# -gt 1 ]]
do
    key="$1"
    value="$2"
    case $key in
        -m|--mongo)
            mongoVersions[$mongoVersionsCount]=$value
            mongoVersionsCount=$(( $mongoVersionsCount + 1 ))
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

# if versions not passed, fill default
if [[ -z $mongoVersions ]]
then
    mongoVersions=("24" "26" "30" "32" "33" "34" "36" "40" "41")
fi

# start bunch of tests
for mongoVersion in ${mongoVersions[@]}
do
    echo -e "\033[1;37m\033[42mTest MongoDB ${mongoVersion} on PHP ${PHPVersion}\033[0m\n"

    PHPMONGO_DSN=mongodb://mongodb${mongoVersion} \
        $projectDir/vendor/bin/phpunit \
        -c $projectDir/tests/phpunit.xml \
        --colors=never \
        $testFilter \
        $testPath \
        | tee $PHPUnitLogDir/mongo${mongoVersion}.log
done
