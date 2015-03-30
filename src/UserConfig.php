<?php
/**
 * @file
 * Contains \Drupal\AppConsole\UserConfig.
 */

namespace Drupal\AppConsole;

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

        if ($baseConfig && $userConfig) {
            $this->config = array_replace_recursive($baseConfig, $userConfig);
        }
    }

    protected function getBaseConfig()
    {
        return $this->readYamlFile($this->file . '/config.yml');
    }

    protected function getUserConfig()
    {
        $userConfig = $this->readYamlFile(
          $this->getUserHomeDir() . '/.console/config.yml'
        );

        return $userConfig;
    }

    public function getUserHomeDir()
    {
        return rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\');
    }
}
