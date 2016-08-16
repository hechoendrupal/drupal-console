<?php

/**
 * @file
 * Contains Drupal\Console\Helper\ConsoleHelper.
 */

namespace Drupal\Console\Helper;

use Symfony\Component\Console\Helper\Helper as BaseHelper;

/**
 * Class Helper
 * @package Drupal\Console\Helper
 * @deprecated
 */
class Helper extends BaseHelper
{
    use HelperTrait;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return;
    }
}
