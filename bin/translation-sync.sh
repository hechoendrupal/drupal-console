#!/bin/bash

if [ $# -eq 3 ]; then
  if [ ! -f $2/$3 ]; then
      echo "File $2/$3 not found!"
      echo "coping $1/$3 to $2/$3"
      command="cp $1/$3 $2/$3"
      $command
  else
      echo "drupal yaml:merge $2/$3 $1/$3 $2/$3"
      command="drupal yaml:merge $2/$3 $1/$3 $2/$3"
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
        echo "drupal yaml:merge $2/$filepath $1/$filepath $2/$filepath"
        command="drupal yaml:merge $2/$filepath $1/$filepath $2/$filepath"
        $command
      fi
   done
fi

if [ $# -eq 0 ]; then
  for dir in ./*
    do
      if [[ "$dir" != "./en" ]]; then
        for f in en/*.yml
          do
            filepath=`basename $f`
            if [ ! -f $dir/$filepath ]; then
              echo "File $dir/$filepath not found!"
              echo "coping en/$filepath to $dir/$filepath"
              command="cp en/$filepath $dir/$filepath"
              $command
            else
              echo "drupal yaml:merge $dir/$filepath en/$filepath $dir/$filepath"
              command="drupal yaml:merge $dir/$filepath en/$filepath $dir/$filepath"
              $command
            fi
          done
      fi
	done
fi  

if [ $# -eq 1 ]; then
  for dir in ./*
    do
      if [[ "$dir" != "./en" ]]; then
        filepath=`basename en/$1.yml`
        if [ ! -f $dir/$filepath ]; then
          echo "File $dir/$filepath not found!"
          echo "coping en/$filepath to $dir/$filepath"
          command="cp en/$filepath $dir/$filepath"
          $command
        else
          echo "drupal yaml:merge $dir/$filepath en/$filepath $dir/$filepath"
          command="drupal yaml:merge $dir/$filepath en/$filepath $dir/$filepath"
          $command
        fi
      fi
	done
fi

if [ $# -gt 3 ]; then
    echo "'$ translation-sync.sh' or '$ translation-sync.sh en es' or '$ translation-sync.sh en es about.yml'"
fi