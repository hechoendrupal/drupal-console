#!/bin/bash

if [ -d "modules/custom/travis" ]; then
    rm -r modules/custom/travis
fi

$CONSOLE_BIN generate:module -n \
--module="My Travis test module" \
--machine-name="travis" \
--module-path="modules/custom" \
--description="My aweasome travis test module" \
--core="8.x" \
--package="Test" \
--dependencies="" \
--test \
--controller

