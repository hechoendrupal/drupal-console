<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\PrintConfigValidationTrait.
 */

namespace Drupal\Console\Command\Config;

trait PrintConfigValidationTrait
{
    protected function printResults($valid)
    {
        if ($valid === true) {
            $this->getIo()->info($this->trans('commands.config.validate.messages.success'));
            return 0;
        }

        foreach ($valid as $key => $error) {
            $this->getIo()->warning($key . ': ' . $error);
        }
        return 1;
    }
}
