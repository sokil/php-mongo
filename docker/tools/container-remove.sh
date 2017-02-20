#!/usr/bin/env bash

# confirm
while true; do
    read -p "Do you really want to drop PHPMongo containers? (y/n): " yn
    case $yn in
        [Yy]* ) break;;
        [Nn]* ) exit;;
        * ) echo "Please answer yes or no.";;
    esac
done

docker ps -a -f NAME=phpmongo --format "{{.Names}}" | xargs -I{} docker rm {}
