<?php

/**
 * @file
 * Contains namespace Drupal\Console\Helper\HelperTrait.
 */

namespace Drupal\Console\Helper;

/**
 * Class HelperTrait
 * @package Drupal\Console\Helper
 */
trait HelperTrait
{
    /**
     * @return \Drupal\Console\Helper\TranslatorHelper
     */
    public function getTranslator()
    {
        if ($this->getHelperSet()->has('translator')) {
            return $this->getHelperSet()->get('translator');
        }

        return null;
    }

    /**
     * @return \Drupal\Console\Helper\SiteHelper
     */
    public function getSite()
    {
        return $this->getHelperSet()->get('site');
    }

    /**
     * return value replaced with service definition.
     * to be removed once helpers are replaced by services.
     */
    public function getChain()
    {
        return $this->getContainerHelper()->get('chain_queue');
    }

    /**
     * @return \Drupal\Console\Helper\StringHelper
     */
    public function getStringHelper()
    {
        return $this->getHelperSet()->get('string');
    }

    /**
     * @return \Drupal\Console\Helper\ValidatorHelper
     */
    public function getValidator()
    {
        return $this->getHelperSet()->get('validator');
    }

    /**
     * @return \Drupal\Console\Helper\DrupalHelper
     */
    public function getDrupalHelper()
    {
        return $this->getHelperSet()->get('drupal');
    }

    /**
     * @return \Drupal\Console\Helper\KernelHelper
     */
    public function getKernelHelper()
    {
        return $this->getHelperSet()->get('kernel');
    }

    /**
     * @return \Drupal\Console\Helper\ShowFileHelper
     */
    public function getShowFileHelper()
    {
        return $this->getHelperSet()->get('showFile');
    }

    /**
     * @return \Drupal\Console\Helper\TwigRendererHelper
     */
    public function getRenderHelper()
    {
        return $this->getHelperSet()->get('renderer');
    }

    /**
     * @return \Drupal\Console\Helper\NestedArrayHelper
     */
    public function getNestedArrayHelper()
    {
        return $this->getHelperSet()->get('nested-array');
    }

    /**
     * @return \Drupal\Console\Helper\CommandDiscoveryHelper
     */
    public function getCommandDiscoveryHelper()
    {
        return $this->getHelperSet()->get('commandDiscovery');
    }

    /**
     * @return \Drupal\Console\Helper\RemoteHelper
     */
    public function getRemoteHelper()
    {
        return $this->getHelperSet()->get('remote');
    }

    /**
     * @return \Drupal\Console\Helper\HttpClientHelper
     */
    public function getHttpClientHelper()
    {
        return $this->getHelperSet()->get('httpClient');
    }

    /**
     * @return \Drupal\Console\Helper\DrupalApiHelper
     */
    public function getDrupalApi()
    {
        return $this->getHelperSet()->get('api');
    }

    /**
     * @return \Drupal\Console\Helper\ContainerHelper
     */
    public function getContainerHelper()
    {
        return $this->getHelperSet()->get('container');
    }
}
