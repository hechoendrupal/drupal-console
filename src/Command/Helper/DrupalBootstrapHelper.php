<?php

/**
 * @file
 *   Contains \Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;

class DrupalBootstrapHelper extends Helper
{
    /**
     * @var bool
     */
    private $booting = false;

    public function getDrupalRoot()
    {
        return $this->booting ? DRUPAL_ROOT : getcwd();
    }

    /**
     * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
     */
    public function getName()
    {
        return 'bootstrap';
    }
}
