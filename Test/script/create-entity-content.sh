#!/bin/bash

. Test/script/setup.sh

$CONSOLE_BIN generate:entity:content --module="travis" --entity-class="TravisEntity" --entity-name="travis-entity"
