<?php
/**
 * @file
 * Contains \Drupal\Console\Annotations\DrupalCommand.
 */

namespace Drupal\Console\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */

class DrupalCommand
{
    /**
     * @var array
     */
    public $dependencies;

    /**
     * @var string
     */
    public $extension;
}
