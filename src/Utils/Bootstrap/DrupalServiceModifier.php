<?php

namespace Drupal\Console\Utils\Bootstrap;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class DrupalServiceModifier implements ServiceModifierInterface
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $serviceTag;

    /**
     * DrupalServiceModifier constructor.
     * @param string $root
     * @param string $serviceTag

     */
    public function __construct(
        $root = null,
        $serviceTag
    ) {
        $this->root = $root;
        $this->serviceTag = $serviceTag;
    }


    /**
     * @inheritdoc
     */
    public function alter(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new AddServicesCompilerPass(
                $this->root
            )
        );
        $container->addCompilerPass(
            new FindCommandsCompilerPass($this->serviceTag)
        );
    }
}
