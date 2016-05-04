<?php
/**
 * @file
 * Contains \Drupal\Console\Command\CommandDependencyResolver.
 */

namespace Drupal\Console\Command;

use Doctrine\Common\Annotations\FileCacheReader;
use Drupal\Console\Annotation\DrupalCommand;
use Drupal\Console\Helper\KernelHelper;
use ReflectionClass;

class CommandDependencyResolver
{

    /**
     * @var CachedReader
     */
    private $reader;

    /**
     * CommandDependencyResolver constructor.
     * @param FileCacheReader $reader
     * @param KernelHelper $kernelSite
     */
    public function __construct(FileCacheReader $reader, KernelHelper $kernelSite)
    {
        $this->reader = $reader;
        $this->kernelSite = $kernelSite;
    }

    /**
     * @param ReflectionClass $class
     * @return array
     */
    public function resolve(ReflectionClass $class)
    {
        /** @var DrupalCommand $definition */
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
