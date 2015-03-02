#!/bin/bash

$CONSOLE_BIN generate:plugin:rest:resource \
--module="travis" \
--class-name="TravisRestResource" \
--plugin-id="travis_rest_resource" \
--plugin-label="Travis Rest Resource" \
--plugin-url="travis_rest_resource" \
--plugin-states="0, 1, 2" \
--no-interaction
