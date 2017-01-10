#!/bin/env bash

sudo apt-get update -q
sudo apt-get install --no-install-recommends -y hhvm-dev

git clone https://github.com/mongodb/mongo-hhvm-driver.git
cd mongo-hhvm-driver
git submodule sync && git submodule update --init --recursive

hphpize
cmake .

make configlib

make -j 1
sudo make install

echo "hhvm.dynamic_extension_path=/usr/local/hhvm/3.9.1/lib/hhvm/extensions/20150212" >> /etc/hhvm/php.ini
echo "hhvm.dynamic_extensions[mongodb]=mongodb.so" >> /etc/hhvm/php.ini
