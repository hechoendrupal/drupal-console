<?php

namespace Drupal\Console\Bootstrap;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\DrupalKernel as DrupalKernelBase;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Class DrupalKernel
 *
 * @package Drupal\Console\Utils
 */
class DrupalKernel extends DrupalKernelBase
{
    /**
     * @var ServiceModifierInterface[]
     */
    protected $serviceModifiers = [];

    /**
     * @inheritdoc
     */
    public static function createFromRequest(Request $request, $class_loader, $environment, $allow_dumping = true, $app_root = null)
    {
        $kernel = new static($environment, $class_loader, $allow_dumping, $app_root);
        static::bootEnvironment($app_root);
        $kernel->initializeSettings($request);
        // Calling the request handle causes that a page request "/" is
        // processed for any console execution even: help or --version and
        // with sites that have globally displayed blocks contexts are not
        // ready for blocks plugins so this causes lot of problems like:
        // https://github.com/hechoendrupal/drupal-console/issues/3091 and
        // https://github.com/hechoendrupal/drupal-console/issues/3553 Also
        // handle does a initializeContainer which originally was invalidated
        // and rebuild at Console Drupal Bootstrap. By disabling handle
        // and processing the boot() at Bootstrap commands that do not
        // depend on requests works well.
        //$kernel->handle($request);
        return $kernel;
    }

    /**
     * @param \Drupal\Core\DependencyInjection\ServiceModifierInterface $serviceModifier
     */
    public function addServiceModifier(ServiceModifierInterface $serviceModifier)
    {
        $this->serviceModifiers[] = $serviceModifier;
    }

    /**
     * @inheritdoc
     */
    protected function getContainerBuilder()
    {
        $container = parent::getContainerBuilder();
        foreach ($this->serviceModifiers as $serviceModifier) {
            $serviceModifier->alter($container);
        }

        return $container;
    }
}
