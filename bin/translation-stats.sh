#!/bin/bash

if [ $# -eq 3 ]; then
  if [ ! -f $2/$3 ]; then
      echo "File $2/$3 not found!"
  else
      echo "drupal yaml:diff --stats $2/$3 $1/$3"
      command="drupal yaml:diff --stats $2/$3 $1/$3"
      stat=`$command`
      total=`echo $stat | cut -f2 -d: | cut -f1 -dD`
      diff=`echo $stat | cut -f3 -d: | cut -f1 -dE`
      equal=`echo $stat | cut -f4 -d:`
      percentage=`echo "scale=1; $diff/$total*100" | bc`
      echo "Total: $total Diff: $diff Equal: $equal Percentage Translation: $percentage"
   fi
fi

if [ $# -eq 2 ]; then
  let translation_total=0;
  let translation_diff=0
  let translation_equal=0
  for f in $1/*.yml
    do
      filepath=`basename $f`
      if [ ! -f $2/$filepath ]; then
        echo "File $2/$filepath not found!"
      else
        #echo "drupal yaml:diff --stats $1/$filepath $2/$filepath"
        command="drupal yaml:diff --stats $1/$filepath $2/$filepath"
        stat=`$command`
        total=`echo $stat | cut -f2 -d: | cut -f1 -dD`
        diff=`echo $stat | cut -f3 -d: | cut -f1 -dE`
        equal=`echo $stat | cut -f4 -d:`
        percentage=`echo "scale=1; $diff/$total*100" | bc`
        echo "$2/$filepath Stats: Total: $total Diff: $diff Equal: $equal Percentage Translation: $percentage"
        translation_total=`echo "scale=1; $translation_total + $total" | bc`
        translation_diff=`echo "scale=1; $translation_diff + $diff" | bc`
        translation_equal=`echo "scale=1; $translation_equal + $equal" | bc`
      fi
   done
   translation_percentage=`echo "scale=3; $translation_diff/$translation_total*100" | bc`
   echo "Translation stats for $2: Percentage Translation:$translation_percentage% Total: $translation_total Diff: $translation_diff Equal: $translation_equal"
fi

if [ $# -eq 0 ]; then
  for dir in ./*
    do
      let translation_total=0;
      let translation_diff=0
      let translation_equal=0
      if [[ "$dir" != "./en" ]]; then
        for f in en/*.yml
            do
              filepath=`basename $f`
              if [ ! -f $dir/$filepath ]; then
                echo "File $dir/$filepath not found!"
              else
                #echo "drupal yaml:diff --stats $1/$filepath $dir/$filepath"
                command="drupal yaml:diff --stats en/$filepath $dir/$filepath"
                stat=`$command`
                total=`echo $stat | cut -f2 -d: | cut -f1 -dD`
                diff=`echo $stat | cut -f3 -d: | cut -f1 -dE`
                equal=`echo $stat | cut -f4 -d:`
                percentage=`echo "scale=1; $diff/$total*100" | bc`
                #echo "$dir/$filepath Stats: Total: $total Diff: $diff Equal: $equal Percentage Translation: $percentage"
                translation_total=`echo "scale=1; $translation_total + $total" | bc`
                translation_diff=`echo "scale=1; $translation_diff + $diff" | bc`
                translation_equal=`echo "scale=1; $translation_equal + $equal" | bc`
              fi
            done
            translation_percentage=`echo "scale=3; $translation_diff/$translation_total*100" | bc`
            echo "Translation stats for $dir: Percentage Translation:$translation_percentage% Total: $translation_total Diff: $translation_diff Equal: $translation_equal"
      fi
    done
fi

if [ $# -eq 1 ] || [ $# -gt 3 ]; then
    echo "'$ translation-stats.sh' or '$ translation-stats.sh en es' or '$ translation-stats.sh en es about.yml'"
fi
