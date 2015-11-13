#!/bin/bash

if [ $# -eq 3 ]; then
  if [ ! -f $2/$3 ]; then
      echo "File $2/$3 not found!"
      echo "coping $1/$3 to $2/$3"
      command="cp $1/$3 $2/$3"
      $command
  else
      echo "console.dev yaml:merge $2/$3 $1/$3 $2/$3"
      command="console.dev yaml:merge $2/$3 $1/$3 $2/$3"
      $command
   fi
fi

if [ $# -eq 2 ]; then
  for f in $1/*.yml
    do
      filepath=`basename $f`
      if [ ! -f $2/$filepath ]; then
        echo "File $2/$filepath not found!"
        echo "coping $1/$filepath to $2/$filepath"
        command="cp $1/$filepath $2/$filepath"
        $command
      else
        echo "console.dev yaml:merge $2/$filepath $1/$filepath $2/$filepath"
        command="console.dev yaml:merge $2/$filepath $1/$filepath $2/$filepath"
        $command
      fi
   done
fi

if [ $# -lt 2 ] || [ $# -gt 3 ]; then
    echo "'$ sync-translations.sh.sh en es' or '$ sync-translations.sh en es about.yml'"
fi
