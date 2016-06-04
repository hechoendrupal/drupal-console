<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ContainerAwareCommandTrait.
 */

namespace Drupal\Console\Command\Shared;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CommandTrait
 * @package Drupal\Console\Command
 */
trait ContainerAwareCommandTrait
{
    use CommandTrait;

    /**
     * @deprecated
     *
     * @param $serviceId
     * @return mixed
     */
    public function hasGetService($serviceId)
    {
        return $this->hasDrupalService($serviceId);
    }

    /**
     * @deprecated
     *
     * @param $id
     * @return mixed
     */
    public function getService($id)
    {
        return $this->getDrupalService($id);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getDrupalService($id)
    {
        if ($this->hasDrupalService($id)) {
            return $this->getDrupalContainer()->get($id);
        }
        return null;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function hasDrupalService($id)
    {
        return $this->getDrupalContainer()->has($id);
    }

    /**
     * Gets the current Drupal container.
     *
     * @return ContainerInterface
     *   A ContainerInterface instance.
     */
    public function getDrupalContainer()
    {
        if (!$this->getApplication()->getKernelHelper()) {
            return null;
        }

        if (!$this->getApplication()->getKernelHelper()->getKernel()) {
            return null;
        }

        return $this->getApplication()->getKernelHelper()->getKernel()->getContainer();
    }
}
