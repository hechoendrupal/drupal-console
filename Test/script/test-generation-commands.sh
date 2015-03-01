#!/bin/bash

FILE=${BASH_SOURCE[0]};
SCRIPT_DIR=$( dirname "$FILE" )

. $SCRIPT_DIR/setup.sh

. $SCRIPT_DIR/create-module.sh
. $SCRIPT_DIR/create-controller.sh
. $SCRIPT_DIR/create-form.sh
. $SCRIPT_DIR/create-entity-config.sh
. $SCRIPT_DIR/create-entity-content.sh
