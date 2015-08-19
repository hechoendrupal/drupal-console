<?php

/**
 * @file
 * Contains Drupal\AppConsole\Generator\AutocompleteGenerator.
 */

namespace Drupal\AppConsole\Generator;

use Drupal\AppConsole\Console\Application;

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
    }
}
