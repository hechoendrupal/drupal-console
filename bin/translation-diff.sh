#!/bin/bash

if [ $# -eq 3 ]; then
  if [ ! -f $2/$3 ]; then
      echo "File $2/$3 not found!"
  else
      echo "drupal yaml:diff $1/$3 $2/$3 --negate"
      command="drupal yaml:diff $2/$3 $1/$3 --negate"
      $command
   fi
fi

if [ $# -eq 2 ]; then
  for f in $1/*.yml
    do
      filepath=`basename $f`
      if [ ! -f $2/$filepath ]; then
        echo "File $2/$filepath not found!"
      else
        echo "drupal yaml:diff $1/$filepath $2/$filepath --negate"
        command="drupal yaml:diff $1/$filepath $2/$filepath --negate"
        $command
      fi
   done
fi


if [ $# -gt 3 ]; then
    echo "'$ translation-diff.sh en es' or '$ translation-diff.sh en es about.yml'"
fi