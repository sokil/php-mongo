#!/usr/bin/env bash

docker ps -a -f NAME=phpmongo --format "{{.Names}}" | xargs -I{} docker rm {}
