<?php

/**
 * @file
 * Contains Drupal\Console\Helper\DrupalApiHelper.
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Helper\Helper;
use Drupal\Console\Utils\Create\Nodes;
use Drupal\Console\Utils\Create\Terms;

/**
 * Class DrupalApiHelper
 * @package Drupal\Console\Helper
 */
class DrupalApiHelper extends Helper
{
    protected $bundles = [];
    protected $vocabularies = [];

    /**
     * @return \Drupal\Console\Utils\Create\Nodes
     */
    public function getCreateNodes()
    {
        $createNodes = new Nodes(
            $this->hasGetService('entity.manager'),
            $this->hasGetService('date.formatter'),
            $this->getBundles()
        );

        return $createNodes;
    }

    /**
     * @return \Drupal\Console\Utils\Create\Nodes
     */
    public function getCreateTerms()
    {
        $createTerms = new Terms(
            $this->hasGetService('entity.manager'),
            $this->hasGetService('date.formatter'),
            $this->getVocabularies()
        );

        return $createTerms;
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
     * @return array
     */
    public function getVocabularies()
    {
        if (!$this->vocabularies) {
            $entityManager = $this->hasGetService('entity.manager');
            $vocabularies = $entityManager->getStorage('taxonomy_vocabulary')->loadMultiple();

            foreach ($vocabularies as $vocabulary) {
                $this->vocabularies[$vocabulary->id()] = $vocabulary->label();
            }
        }

        return $this->vocabularies;
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
