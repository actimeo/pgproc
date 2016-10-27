#! /bin/sh

rm -rf .traces
./vendor/bin/phpunit --stop-on-error tests

