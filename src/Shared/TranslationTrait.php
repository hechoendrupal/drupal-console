<?php

/**
 * @file
 * Contains Drupal\Console\Shared\TranslationTrait.
 */

namespace Drupal\Console\Shared;

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
