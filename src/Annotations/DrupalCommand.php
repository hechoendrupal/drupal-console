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
     * @Enum({"module", "theme", "profile", "library"})
     */
    public $extensionType;

    /**
     * @var array
     */
    public $dependencies;

    /**
     * @Enum({"none", "site", "install"})
     */
    public $bootstrap;
}
