<?php

namespace Drupal\Console\Bootstrap;

use Drupal\Core\Update\UpdateKernel as DrupalKernelBase;

/**
 * Class DrupalUpdateKernel
 *
 * @package Drupal\Console\Bootstrap
 */
class DrupalUpdateKernel extends DrupalKernelBase
{
    use DrupalKernelTrait;
}
