#!/bin/bash

. Test/script/setup.sh

$CONSOLE_BIN generate:controller --module="travis" --class-name="TravisController" --method-name="index" --route="/index" --services="twig" --test -n
