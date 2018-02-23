<?php

namespace Drupal\Console\Bootstrap;

use Dflydev\DotAccessConfiguration\ConfigurationInterface;
use Drupal\Console\Override\ConfigSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Console\Utils\TranslatorManager;
use Drupal\Core\Cache\ListCacheBinsPass;

/**
 * DrupalCompilerPass
 */
class DrupalCompilerPass implements CompilerPassInterface
{
    protected $configuration;

    /**
     * DrupalCompilerPass constructor.
     *
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

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

        $skipValidateSiteUuid = $this->configuration
            ->get('application.overrides.config.skip-validate-site-uuid');

        if ($skipValidateSiteUuid) {
            // override system.config_subscriber
            $container
                ->getDefinition('system.config_subscriber')
                ->setClass(ConfigSubscriber::class);
        }

        // Set console.invalid_commands service
        $container
            ->get('console.key_value_storage')
            ->set('invalid_commands', null);

        // Set console.cache_key service
        $container->set(
            'console.cache_key',
            null
        );
    }
}
