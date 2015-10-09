<?php

/**
 * @file
 * Contains Drupal\Console\Generator\AutocompleteGenerator.
 */

namespace Drupal\Console\Generator;

class AutocompleteGenerator extends Generator
{
    /**
     * @param  $executable
     */
    public function generate($user_path, $executable)
    {
        $parameters = array(
          'executable' => $executable,
        );

        $this->renderFile(
            'autocomplete/console.rc.twig',
            $user_path.'console.rc',
            $parameters
        );

        $this->renderFile(
            'autocomplete/console.fish.twig',
            $user_path.'drupal.fish',
            $parameters
        );
    }
}
