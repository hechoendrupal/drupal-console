<?php

/**
 * @file
 * Contains \Drupal\Console\UserConfig.
 */

namespace Drupal\Console;

class UserConfig extends Config
{
    public function __construct($file = null)
    {
        parent::__construct($file);
        $this->mergeConfig();
    }

    protected function mergeConfig()
    {
        $baseConfig = $this->getBaseConfig();
        $userConfig = $this->getUserConfig();

        $this->config = array_replace_recursive($baseConfig, $userConfig);
    }

    protected function getBaseConfig()
    {
        if (file_exists(__DIR__.'/../config.yml')) {
            return $this->readYamlFile(__DIR__.'/../config.yml');
        }

        return [];
    }

    protected function getUserConfig()
    {
        if (file_exists($this->getUserHomeDir().'/.console/config.yml')) {
            return $this->readYamlFile(
                $this->getUserHomeDir().'/.console/config.yml'
            );
        }

        return [];
    }

}
