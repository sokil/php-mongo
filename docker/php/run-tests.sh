#!/bin/bash

file=$(readlink -f $0);
projectDir=$(dirname $file)/../..

PHPVersion=$(php -r "echo phpversion();");
PHPUnitLogDir=$projectDir/docker/share/phpunit/${PHPVersion}
testPath=$projectDir/tests

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
        *)
        ;;
    esac
    shift
done

# if versions not passed, fill default
if [[ -z $mongoVersions ]]
then
    mongoVersions=("24" "26" "30" "32" "33" "34")
fi

# start bunch of tests
for mongoVersion in ${mongoVersions[@]}
do
    echo "Test MongoDB ${mongoVersion} on PHP ${PHPVersion}"

    PHPMONGO_DSN=mongodb://mongodb${mongoVersion} \
        $projectDir/vendor/bin/phpunit \
        -c $projectDir/tests/phpunit.xml \
        --colors=never \
        $testPath \
        > $PHPUnitLogDir/mongo${mongoVersion}.log
done
