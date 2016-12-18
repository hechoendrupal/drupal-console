<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\PrintConfigValidationTrait.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Console\Style\DrupalStyle;

trait PrintConfigValidationTrait
{
    protected function printResults($valid, DrupalStyle $io)
    {
        if ($valid === true) {
            $io->info($this->trans('commands.config.validate.messages.success'));
            return 0;
        }

        foreach ($valid as $key => $error) {
            $io->warning($key . ': ' . $error);
        }
        return 1;
    }
}
