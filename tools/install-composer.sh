#!/bin/bash

# Set the composer version to install.
# COMPOSER_VERSION=1.0.0-alpha10

# Download composer.
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
if [ -z ${COMPOSER_VERSION+x} ]; then
    echo -e "Installing composer (latest)...\n"
    curl -sS https://getcomposer.org/installer | php -- --install-dir="$DIR/../"
else
    echo -e "Installing composer ($COMPOSER_VERSION)...\n"
    curl -sS https://getcomposer.org/installer | php -- --install-dir="$DIR/../" --version="$COMPOSER_VERSION"
fi
