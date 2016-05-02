<?php
/**
 * @file
 * Contains \Drupal\Console\Command\CommandDependencyResolver.
 */

namespace Drupal\Console\Command;

use Doctrine\Common\Annotations\CachedReader;
use Drupal\Console\Annotation\DrupalCommand;
use Drupal\Console\Helper\KernelHelper;
use ReflectionClass;

class CommandDependencyResolver
{

    /**
     * @var CachedReader
     */
    private $reader;

    public function __construct(CachedReader $reader, KernelHelper $kernelSite)
    {
        $this->reader = $reader;
        $this->kernelSite = $kernelSite;
    }

    public function resolve(ReflectionClass $class)
    {
        /** @var DrupalCommand $definition */
        $definitions = $this->reader->getClassAnnotations($class);

        $container = $this->kernelSite->getKernel()->getContainer();

        foreach ($definitions as $definition) {
            try {
                foreach ($definition->dependencies as $dependency) {
                    $container->get('module_handler')->getModule($dependency);
                }
            } catch (\InvalidArgumentException $e) {
                return false;
            }
        }

        return true;
    }

}
