<?php

namespace Drupal\Console\Bootstrap;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Dflydev\DotAccessConfiguration\ConfigurationInterface;

/**
 * Class DrupalServiceModifier
 *
 * @package Drupal\Console\Bootstrap
 */
class DrupalServiceModifier implements ServiceModifierInterface
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $commandTag;

    /**
     * @var string
     */
    protected $generatorTag;

    protected $configuration;

    /**
     * DrupalServiceModifier constructor.
     *
     * @param string                 $root
     * @param string                 $serviceTag
     * @param string                 $generatorTag
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        $root = null,
        $serviceTag,
        $generatorTag,
        $configuration
    ) {
        $this->root = $root;
        $this->commandTag = $serviceTag;
        $this->generatorTag = $generatorTag;
        $this->configuration = $configuration;
    }

    /**
     * @inheritdoc
     */
    public function alter(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new DrupalCompilerPass($this->configuration)
        );
    }
}
