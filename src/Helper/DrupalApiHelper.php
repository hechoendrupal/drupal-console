<?php

/**
 * @file
 * Contains Drupal\Console\Helper\DrupalApiHelper.
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Helper\Helper;
use Drupal\Console\Utils\Content;

/**
 * Class DrupalApiHelper
 * @package Drupal\Console\Helper
 */
class DrupalApiHelper extends Helper
{
    protected $bundles = [];

    /**
     * @return \Drupal\Console\Utils\Content
     */
    public function getContentGenerator()
    {
        $contentNode = new Content(
            $this->hasGetService('entity.manager'),
            $this->getBundles()
        );

        return $contentNode;
    }

    /**
     * @return array
     */
    public function getBundles()
    {
        if (!$this->bundles) {
            $entityManager = $this->hasGetService('entity.manager');
            $nodeTypes = $entityManager->getStorage('node_type')->loadMultiple();

            foreach ($nodeTypes as $nodeType) {
                $this->bundles[$nodeType->id()] = $nodeType->label();
            }
        }

        return $this->bundles;
    }

    /**
     * @param $serviceId
     * @return mixed
     */
    public function hasGetService($serviceId)
    {
        if (!$this->getContainer()) {
            return null;
        }

        if ($this->getContainer()->has($serviceId)) {
            return $this->getContainer()->get($serviceId);
        }

        return null;
    }

    /**
     * Gets the current container.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     *   A ContainerInterface instance.
     */
    protected function getContainer()
    {
        if (!$this->getKernelHelper()) {
            return null;
        }

        if (!$this->getKernelHelper()->getKernel()) {
            return null;
        }

        return $this->getKernelHelper()->getKernel()->getContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'api';
    }
}
