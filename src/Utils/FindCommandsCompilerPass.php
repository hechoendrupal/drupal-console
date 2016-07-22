<?php

namespace Drupal\Console\Utils;

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
    protected $tagId;

    /**
     * FindCommandsCompilerPass constructor.
     * @param $tagId
     */
    public function __construct($tagId)
    {
        $this->tagId = $tagId;
    }

    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(
            $this->tagId
        );

        $commands = [];
        foreach ($taggedServices as $id => $tags) {
            $commands[] = $id;
        }

        $container->setParameter('console.commands', $commands);
    }
}
