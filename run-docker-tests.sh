#!/usr/bin/env bash

# init php and mongo versions
phpVersions=()
phpVersionsCount=0
dockerCommand="bash /phpmongo/docker/php/run-tests.sh"
testPath=""

# get php and mongo versions from input arguments
while [[ $# -gt 1 ]]
do
    key="$1"
    value="$2"
    case $key in
        -p|--php)
            phpVersions[$phpVersionsCount]=$value
            phpVersionsCount=$(( $phpVersionsCount + 1 ))
            shift
        ;;
        -m|--mongo)
            dockerCommand="${dockerCommand} -m ${value}"
            shift
        ;;
        -t|--test)
            testPath=$value
            shift
        ;;
        *)
        ;;
    esac
    shift
done

# if php versions not passed, fill default
if [[ -z $phpVersions ]]
then
    phpVersions=("56" "70" "71")
fi

# add path to test file
if [[ ! -z $testPath ]]
then
    dockerCommand="${dockerCommand} -t ${testPath}"
fi

# start bunch of tests
for phpVersion in ${phpVersions[@]}; do
    docker exec -it phpmongo_php${phpVersion} $dockerCommand
done
