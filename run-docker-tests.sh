#!/usr/bin/env bash

echo "Test PHP 5.6"
docker exec -it phpmongo_php56 bash /phpmongo/docker/php/run-tests.sh

echo "Test PHP 7.0"
docker exec -it phpmongo_php70 bash /phpmongo/docker/php/run-tests.sh

echo "Test PHP 7.1"
docker exec -it phpmongo_php71 bash /phpmongo/docker/php/run-tests.sh
