#!/usr/bin/env bash

########################################################################################################################
# This script executed on host machine.
# You can optionally pass parameters:
#   -p : version of PHP without dots.
#   -m : version of MongoDB without dots.
#   -t : path to concrete test file, relative to ./tests/
# For example:
# $ ./run-docker-tests.sh -p 71 -m 32 -t CursorTest.php
# 
# Actual list of supported versions may be found in docker's compose (./docker/compose.yml)
########################################################################################################################

PROJECT_DIR=$(dirname $(readlink -f $0))

# init php version
phpVersions=()
phpVersionsCount=0

# init mongo version
mongoVersions=()
mongoVersionsCount=0

# test pattern
testPath=""
testFilter=""

# docker command pattern
dockerRunTestCommand="bash /phpmongo/docker/php/run-tests.sh"

# get php and mongo versions from input arguments
while [[ $# -gt 1 ]]
do
    key="$1"
    value="$2"
    case $key in
        -p|--php)
            phpVersions[$phpVersionsCount]=${value}
            phpVersionsCount=$(( ${phpVersionsCount} + 1 ))
            shift
        ;;
        -m|--mongo)
            mongoVersions[$mongoVersionsCount]=$value
            mongoVersionsCount=$(( ${mongoVersionsCount} + 1 ))
            shift
        ;;
        -t|--test)
            testPath=${value}
            shift
        ;;
        -f|--filter)
            testFilter=${value}
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
    phpVersions=("71" "72" "73" "74")
fi

# if versions not passed, fill default
if [[ -z $mongoVersions ]]
then
    mongoVersions=("24" "26" "30" "32" "33" "34" "36" "40" "41")
fi

# add path to test file
if [[ ! -z $testPath ]]
then
    dockerRunTestCommand="${dockerRunTestCommand} -t ${testPath}"
fi

# add path to test file
if [[ ! -z $testFilter ]]
then
    dockerRunTestCommand="${dockerRunTestCommand} -f ${testFilter}"
fi

# start bunch of tests
for phpVersion in ${phpVersions[@]}; do
    docker-compose -f ${PROJECT_DIR}/docker/compose.yml up -d php${phpVersion}

    # wait for container initialised
    # see docker/php/init.sh for details
    SHARE_DIR=${PROJECT_DIR}/docker/share/${phpVersion:0:1}.${phpVersion:1:1}
    echo "Waiting for docker container initialisation ..."
    while [[ ! -d ${SHARE_DIR} ]]; do
        echo -n .
        sleep 1
    done

    for mongoVersion in ${mongoVersions[@]}; do
        echo -e "\n\033[1;37m\033[42mTest MongoDB ${mongoVersion} on PHP ${phpVersion}\033[0m\n"
        docker-compose -f ${PROJECT_DIR}/docker/compose.yml up -d mongodb${mongoVersion}
        until docker exec -it phpmongo_mongo${mongoVersion} mongo --eval "print(\"waited for connection\")" > /dev/null
        do
            echo -n .
            sleep 1
        done
        docker exec -it phpmongo_php${phpVersion} $dockerRunTestCommand -m $mongoVersion
        docker-compose -f ${PROJECT_DIR}/docker/compose.yml stop mongodb${mongoVersion}
    done
    docker-compose -f ${PROJECT_DIR}/docker/compose.yml stop php${phpVersion}
done
