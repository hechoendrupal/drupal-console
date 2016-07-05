<?php

namespace Drupal\Console\Utils;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Console\Utils\FindCommandsCompilerPass;

class DrupalServiceModifier implements ServiceModifierInterface
{
    /**
     * @inheritdoc
     */
    public function alter(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new FindCommandsCompilerPass('console.command')
        );
    }
}
