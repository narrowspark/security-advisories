#!/usr/bin/env bash

source ./build/travis/try_catch.sh
source ./build/travis/tfold.sh

try
    tfold "Narrowspark Security Advisories Test Suite" "$TEST -c ./phpunit.xml.dist";
catch || {
    exit 1
}