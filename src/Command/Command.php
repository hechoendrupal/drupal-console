<?php

namespace Drupal\Console\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Drupal\Console\Helper\HelperTrait;

/**
 * Class Command
 * @package Drupal\Console\Command
 */
abstract class Command extends BaseCommand
{
    use HelperTrait;

    /**
     * @var string
     */
    protected $module;

    /**
     * @var array
     */
    protected $dependencies;

    /**
     * @param HelperSet $helperSet
     */
    public function __construct(HelperSet $helperSet)
    {
        $this->setHelperSet($helperSet);
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param string $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * @param $key string
     *
     * @return string
     */
    public function trans($key)
    {
        return $this->getTranslator()->trans($key);
    }

    /**
     * @param $moduleName string
     *
     * @param $moduleName
     */
    public function addDependency($moduleName)
    {
        $this->dependencies[] = $moduleName;
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * @return \Drupal\Console\Application;
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}
