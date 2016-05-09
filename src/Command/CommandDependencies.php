<?php
/**
 * @file
 * Contains \Drupal\Console\Command\CommandDependencyResolver.
 */

namespace Drupal\Console\Command;

use Doctrine\Common\Annotations\FileCacheReader;
use Drupal\Console\Annotation\DrupalCommand;
use ReflectionClass;

class CommandDependencies
{
    /**
     * @var FileCacheReader
     */
    private $reader;

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
    public function read(ReflectionClass $class)
    {
        /**
 * @var DrupalCommand $definition 
*/
        $definitions = $this->reader->getClassAnnotations($class);

        $dependencies = [];
        foreach ($definitions as $definition) {
            if ($definition instanceof DrupalCommand) {
                foreach ($definition->dependencies as $dependency) {
                    $dependencies[] = $dependency;
                }
            }
        }

        return $dependencies;
    }
}
