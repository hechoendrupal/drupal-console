<?php

/**
 * @file
 * Contains Drupal\Console\Helper\ConsoleHelper.
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Helper\HelperTrait;

class Helper extends \Symfony\Component\Console\Helper\Helper
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
