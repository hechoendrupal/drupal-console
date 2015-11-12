#!/bin/bash

if [ $# -eq 1 ]; then
  for f in $1/*.yml
    do
      filepath=`basename $f`
      if [ ! -f en/$filepath ]; then
        echo "File $1/$filepath not found in English version!"
        echo "removing $1/$filepath"
        command="rm $1/$filepath"
        $command
      fi
   done
fi

if [ $# -eq 0 ] || [ $# -gt 2 ]; then
    echo "'$ translation-clean-up.sh es"
fi
