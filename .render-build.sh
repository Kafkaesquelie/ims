#!/usr/bin/env bash
set -o errexit

# Install MySQL extensions for PHP
apt-get update
apt-get install -y php-mysql
