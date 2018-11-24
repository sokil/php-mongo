#!/usr/bin/env bash

########################################################################################################################
# Before running tests start docker container by calling docker-compose:
# $ docker-compose -f docker/compose.yml up -d
# You can start only some services my passing their names:
# $ docker-compose -f docker/compose.yml up -d php56 mongodb32
#
# This script executed on host machine.
# You can optionally pass parameters:
#   -p : version of PHP without dots. Currently supported 56, 70, 71
#   -m : version of MongoDB without dots. Currently supported 24, 26, 30, 32, 33, 34
#   -t : path to concrete test file, relative to ./tests/
# For example:
# $ ./run-docker-tests.sh -p 56 -m 32 -t CursorTest.php
# 
# Actual list of supported versions may be found in docker's compose (./docker/compose.yml)
########################################################################################################################

# init php and mongo versions
phpVersions=()
phpVersionsCount=0
dockerCommand="bash /phpmongo/docker/php/run-tests.sh"
testPath=""
testFilter=""

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
        -f|--filter)
            testFilter=$value
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
    phpVersions=("56" "70" "71" "72" "73")
fi

# add path to test file
if [[ ! -z $testPath ]]
then
    dockerCommand="${dockerCommand} -t ${testPath}"
fi

# add path to test file
if [[ ! -z $testFilter ]]
then
    dockerCommand="${dockerCommand} -f ${testFilter}"
fi

# start bunch of tests
for phpVersion in ${phpVersions[@]}; do
    docker exec -it phpmongo_php${phpVersion} $dockerCommand
done
