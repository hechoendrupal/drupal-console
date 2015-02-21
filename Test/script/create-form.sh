#!/bin/bash

. Test/script/setup.sh

$CONSOLE_BIN generate:form:config \
--module="travis" \
--class-name="FormTravis" \
--form-id="travis-form" \
--services="database" \
--inputs="key" \
--routing \
--no-interaction
