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
     * @return \Drupal\Console\Helper\MessageHelper
     */
    public function getMessageHelper()
    {
        return $this->getHelperSet()->get('message');
    }

    /**
     * @return \Drupal\Console\Utils\StringUtils
     */
    public function getStringUtils()
    {
        return $this->getHelperSet()->get('stringUtils');
    }

    /**
     * @return \Drupal\Console\Utils\Validators
     */
    public function getValidator()
    {
        return $this->getHelperSet()->get('validators');
    }

    /**
     * @return \Drupal\Console\Helper\DialogHelper
     */
    protected function getDialogHelper()
    {
        return $this->getHelperSet()->get('dialog');
    }

    /**
     * @return \Symfony\Component\Console\Helper\QuestionHelper
     */
    protected function getQuestionHelper()
    {
        return $this->getHelperSet()->get('question');
    }

    /**
     * @return \Drupal\Console\Helper\NestedArrayHelper
     */
    protected function getRenderHelper()
    {
        return $this->getHelperSet()->get('renderer');
    }

    /**
     * @return \Drupal\Console\Helper\NestedArrayHelper
     */
    protected function getNestedArrayHelper()
    {
        return $this->getHelperSet()->get('nested-array');
    }
}
