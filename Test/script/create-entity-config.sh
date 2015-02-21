#!/bin/bash

. Test/script/setup.sh

$CONSOLE_BIN generate:entity:config \
--module="travis" \
--entity-class="TravisEntityConfig" \
--entity-name="travis-entity-config"

