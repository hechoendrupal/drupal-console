<?php
/**
 * @file
 * Contains \Drupal\Console\Annotation\DrupalCommand.
 */

namespace Drupal\Console\Annotation;

/**
 * @Annotation
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
