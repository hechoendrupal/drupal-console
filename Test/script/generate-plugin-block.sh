#!/bin/bash

$CONSOLE_BIN generate:plugin:block \
--module="traivs" \
--class-name="TravisPluginBlock" \
--label="Travis plugin block" \
--plugin-id="travis_block" \
--no-interaction
