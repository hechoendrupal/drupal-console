<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\BreakPointGenerator.
 */

namespace Drupal\Console\Generator;

class  BreakPointGenerator extends Generator
{
    /**
     * Generator BreakPoint.
     *
     * @param $theme
     * @param $breakpoints
     * @param $machine_name
     */
    public function generate($theme, $breakpoints, $machine_name)
    {
        $parameters = [
          'theme' => $theme,
          'breakpoints' => $breakpoints,
          'machine_name' => $machine_name
        ];
        
        $theme_path =  $this->getSite()->getThemePath($theme);

        $this->renderFile(
            'theme/breakpoints.yml.twig',
            $theme_path .'/'.$machine_name.'.breakpoints.yml',
            $parameters,
            FILE_APPEND
        );
    }
}
