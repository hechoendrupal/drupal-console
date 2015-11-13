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

if [ $# -eq 0 ]; then
  for dir in ./*
    do
      if [[ "$dir" != "./en" ]]; then
        for f in $dir/*.yml
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
	done
fi

if [ $# -ge 2 ]; then
    echo "'$ translation-clean-up.sh' or '$ translation-clean-up.sh es'"
fi
