#!/bin/bash

$CONSOLE_BIN generate:form:config \
--module="travis" \
--class-name="FormTravis" \
--form-id="travis-form" \
--services="database" \
--inputs="key" \
--routing \
--no-interaction
