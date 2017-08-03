<?php

namespace Drupal\Console\Bootstrap;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * FindCommandsCompilerPass
 */
class FindCommandsCompilerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $serviceTag;

    /**
     * FindCommandsCompilerPass constructor.
     *
     * @param $serviceTag
     */
    public function __construct($serviceTag)
    {
        $this->serviceTag = $serviceTag;
    }

    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(
            $this->serviceTag
        );

        $commands = [];
        foreach ($taggedServices as $id => $tags) {
            $commands[] = $id;
        }

        $container->setParameter('drupal.commands', $commands);
    }
}
