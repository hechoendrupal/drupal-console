<?php

namespace Drupal\Console\Command;

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
        return $this->getService($serviceId);
    }

    /**
     * @deprecated
     *
     * @param $id
     * @return mixed
     */
    public function getService($id)
    {
        if ($this->hasService($id)) {
            return $this->getDrupalContainer()->get($id);
        }
        return null;
    }

    /**
     * @deprecated
     *
     * @param $id
     * @return mixed
     */
    public function hasService($id)
    {
        return $this->getDrupalContainer()->has($id);
    }

    /**
     * Gets the current container.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
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
