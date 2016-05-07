<?php
/**
 * @file
 * Contains \Drupal\Console\Command\CommandDependencyResolver.
 */

namespace Drupal\Console\Command;

use Doctrine\Common\Annotations\FileCacheReader;
use Drupal\Console\Annotation\DrupalCommand;
use \ReflectionClass;

class CommandDependencies
{
    /**
     * @var FileCacheReader
     */
    private $reader;

    private $dependencies = [];

    /**
     * CommandDependencyResolver constructor.
     * @param FileCacheReader $reader
     */
    public function __construct(FileCacheReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param ReflectionClass $class
     * @return array
     */
    public function read(ReflectionClass $class, $name)
    {
        $definitions = $this->reader->getClassAnnotations($class);
        foreach ($definitions as $definition) {
            if ($definition instanceof DrupalCommand) {
                if ($definition->dependencies) {
                    foreach ($definition->dependencies as $dependency) {
                        $this->dependencies[$name][] = $dependency;
                    }
                }
            }
        }
    }

    /**
     * @param $command string
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }
}
