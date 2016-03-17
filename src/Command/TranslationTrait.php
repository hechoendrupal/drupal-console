<?php

/**
 * @file
 * Contains Drupal\Console\Command\ServicesTrait.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Style\DrupalStyle;

trait TranslationTrait
{
    /**
     * @param string $value
     *
     * @return mixed
     */
    protected function isYamlKey($value)
    {
        if (!strstr($value, ' ') && strstr($value, '.')) {
            return true;
        }

        return false;
    }
}
