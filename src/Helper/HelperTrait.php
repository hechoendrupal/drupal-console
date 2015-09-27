<?php

/**
 * @file
 * Contains namespace Drupal\Console\Helper\HelperTrait.
 */

namespace Drupal\Console\Helper;

/**
 * Class HelperTrait
 * @package Drupal\Console\Helper
 */
trait HelperTrait
{
    /**
     * @return \Drupal\Console\Helper\MessageHelper
     */
    public function getMessageHelper()
    {
        return $this->getHelperSet()->get('message');
    }
}
