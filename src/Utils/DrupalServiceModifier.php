<?php

namespace Drupal\Console\Utils;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class DrupalServiceModifier implements ServiceModifierInterface
{
    /**
     * @var string
     */
    protected $consoleRoot;

    /**
     * @var string
     */
    protected $serviceTag;

    /**
     * DrupalServiceModifier constructor.
     * @param string $consoleRoot
     * @param string $serviceTag
     */
    public function __construct(
        $consoleRoot = null,
        $serviceTag = null
    ) {
        $this->consoleRoot = $consoleRoot;
        $this->serviceTag = $serviceTag;
    }


    /**
     * @inheritdoc
     */
    public function alter(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new AddCommandsCompilerPass($this->consoleRoot)
        );
        $container->addCompilerPass(
            new FindCommandsCompilerPass($this->serviceTag)
        );
    }
}
