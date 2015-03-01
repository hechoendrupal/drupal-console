#!/bin/bash

$CONSOLE_BIN generate:controller -n \
--module="travis" \
--class-name="TravisController" \
--method-name="index" \
--route="/index" \
--services="twig" \
--test
