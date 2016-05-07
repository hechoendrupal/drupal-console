<?php

namespace Drupal\Console\DependencyInjection\Compiler;

use Drupal\Console\Command\CommandDependencies;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AnnotationRegistryPass implements CompilerPassInterface
{

    /**
     * @var CommandDependencies
     */
    private $resolver;

    /**
     * AnnotationRegistryPass constructor.
     * @param CommandDependencies $resolver
     */
    public function __construct(CommandDependencies $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        foreach($container->findTaggedServiceIds("console.command") as $name => $serviceId) {
            $definition = $container->getDefinition($name);
            $commandName = $container->get($name)->getName();

            if (!$definition->isAbstract()) {
                $className = $definition->getClass();
                $command = new \ReflectionClass($className);
                $this->resolver->read($command, $commandName);
            }
        }
    }

}