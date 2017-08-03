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
        $kernel->handle($request);
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
