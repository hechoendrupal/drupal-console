<?php

namespace Drupal\Console\Bootstrap;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Console\Utils\TranslatorManager;
use Drupal\Core\Cache\ListCacheBinsPass;

/**
 * DrupalCompilerPass
 */
class DrupalCompilerPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        // The AddServicesCompilerPass cache pass is executed before the
        // ListCacheBinsPass causing exception: ParameterNotFoundException: You
        // have requested a non-existent parameter "cache_default_bin_backends"
        $cache_pass = new ListCacheBinsPass();
        $cache_pass->process($container);

        // Override TranslatorManager service definition
        $container
            ->getDefinition('console.translator_manager')
            ->setClass(TranslatorManager::class);

        // Set console.invalid_commands service
        $container->set(
            'console.invalid_commands',
            null
        );

        // Set console.cache_key service
        $container->set(
            'console.cache_key',
            null
        );
    }
}
