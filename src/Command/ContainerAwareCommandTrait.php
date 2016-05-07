<?php

namespace Drupal\Console\Command;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CommandTrait
 * @package Drupal\Console\Command
 */
trait ContainerAwareCommandTrait
{
    use CommandTrait;

    /**
     * * Return a service object registered on the container.
     *
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
     * Return a service object registered on the container.
     *
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
     * Validate if a service object is registered on the container.
     *
     * @param $id
     * @return mixed
     */
    public function hasDrupalService($id)
    {
        return $this->getDrupalContainer()->has($id);
    }

    /**
     * Gets the Drupal container.
     *
     * @return ContainerInterface
     *   A ContainerInterface instance.
     */
    private function getDrupalContainer()
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
