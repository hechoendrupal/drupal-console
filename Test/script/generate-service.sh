#!/bin/bash

$CONSOLE_BIN generate:service \
--module="travis" \
--service-name="travis.service" \
--class-name="TravisService" \
--interface="yes" \
--no-interaction