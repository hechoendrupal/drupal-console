<?php

namespace Drupal\AppConsole\Test;

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    public $dir;

    protected function setup()
    {
        $this->setUpTemporalDirectory();
        if (!defined('DRUPAL_ROOT')) {
            define('DRUPAL_ROOT', getcwd());
        }
    }

    public function setUpTemporalDirectory()
    {
        $this->dir = sys_get_temp_dir() . "/modules";
    }
}
