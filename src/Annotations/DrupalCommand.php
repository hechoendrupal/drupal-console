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
     * @var string
     */
    public $extension;

    /**
     * @var string
     */
    public $extensionType;

    /**
     * @var array
     */
    public $dependencies;
}
