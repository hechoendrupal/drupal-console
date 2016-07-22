<?php

namespace Drupal\Console\Utils;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class DrupalServiceModifier implements ServiceModifierInterface
{
    /**
     * @inheritdoc
     */
    public function alter(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new AddCommandsCompilerPass()
        );
        $container->addCompilerPass(
            new FindCommandsCompilerPass('console.command')
        );
    }
}
