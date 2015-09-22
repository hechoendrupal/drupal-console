<?php
/**
 * Created by PhpStorm.
 * User: dmouse
 * Date: 9/18/15
 * Time: 9:42 AM
 */

namespace Drupal\AppConsole;


use Symfony\Component\Debug\Exception\FatalThrowableError;

class RemoteConfig extends Config
{

    public function exist($target)
    {
        return file_exists($this->getUserHomeDir() . '/.console/remote/' . $target . '.yml');
    }

    public function getTarget($target)
    {
        if ($this->exist($target)) {
            $config = $this->readYamlFile($this->getUserHomeDir() . '/.console/remote/' . $target . '.yml');
            return $config;
        }
        else {
            return new \ThrowExceptionTestCase();
        }
    }
}