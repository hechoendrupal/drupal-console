#!/bin/bash

. Test/script/setup.sh

echo $CONSOLE_BIN
$CONSOLE_BIN generate:module --module="My Travis test module" --machine-name="travis" --module-path="modules/custom" --description="My aweasome travis test module" --core="8.x" --package="Test" --dependencies="" --test --controller -n

