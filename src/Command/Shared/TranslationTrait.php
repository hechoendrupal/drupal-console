<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\TranslationTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;

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
