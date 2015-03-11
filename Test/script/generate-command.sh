#!/bin/bash

$CONSOLE_BIN generate:command -n \
--module="travis" \
--class-name="TravisCommand" \
--command="travis:command" \
--container
