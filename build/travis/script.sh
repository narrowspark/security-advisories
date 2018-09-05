#!/usr/bin/env bash

source ./build/travis/try_catch.sh
source ./build/travis/tfold.sh

for f in ./src/*; do
    if [[ -d "$f" && ! -L "$f" ]]; then
        TESTSUITE="Narrowspark Test Suite";

        try
            tfold "$TESTSUITE" "$TEST -c ./phpunit.xml.dist";
        catch || {
            exit 1
        }
    fi
done