#!/bin/sh
find . -name \*.php -exec php -l {} \; |more

