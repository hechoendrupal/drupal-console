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
        return $this->getHelperSet()->get('translator');
    }

    /**
     * @return \Drupal\Console\Helper\SiteHelper
     */
    public function getSite()
    {
        return $this->getHelperSet()->get('site');
    }

    /**
     * @return \Drupal\Console\Helper\ChainCommandHelper
     */
    public function getChain()
    {
        return $this->getHelperSet()->get('chain');
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
     * @return \Drupal\Console\Helper\MessageHelper
     */
    public function getMessageHelper()
    {
        return $this->getHelperSet()->get('message');
    }

    /**
     * @return \Symfony\Component\Console\Helper\QuestionHelper
     */
    public function getQuestionHelper()
    {
        return $this->getHelperSet()->get('question');
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
     * @return \Symfony\Component\Console\Helper\FormatterHelper
     */
    public function getFormatterHelper()
    {
        return $this->getHelperSet()->get('formatter');
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
}
