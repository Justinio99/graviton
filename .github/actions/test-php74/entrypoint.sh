#!/bin/bash

bash prepare-for-tests.sh

export MONGODB_URI=mongodb://mongodb:27017/db
composer install

composer check

php vendor/bin/phpunit
