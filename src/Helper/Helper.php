<?php

/**
 * @file
 * Contains Drupal\Console\Helper\ConsoleHelper.
 */

namespace Drupal\Console\Helper;

use Symfony\Component\Console\Helper\Helper as BaseHelper;
use Drupal\Console\Helper\HelperTrait;

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
