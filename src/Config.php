<?php

/**
 * @file
 */

namespace Drupal\AppConsole;

class Config
{
    /** @var Symfony\Component\Yaml\Parser $parser  */
    protected $parser;

    protected $root_path;

    protected $config;

    public function __construct($parser, $root_path)
    {
        $this->parser = $parser;
        $this->root_path = $root_path;
        $this->mergeConfig();
    }

    protected function readYamlFile($path_file)
    {
        if (file_exists($path_file)) {
            return $this->parser->parse(file_get_contents($path_file));
        } else {
            return [];
        }
    }

    protected function mergeConfig()
    {
        $baseConfig = $this->getBaseConfig();
        $userConfig = $this->getUserConfig();

        $this->config = array_replace_recursive($baseConfig, $userConfig);
    }

    protected function getBaseConfig()
    {
        return $this->readYamlFile($this->root_path . '/config.yml');
    }

    protected function getUserConfig()
    {
        $userConfig = $this->readYamlFile(
          $this->getUserHomeDir() . '/.console/config.yml'
        );
        return $userConfig;
    }

    protected function getUserHomeDir()
    {
        return rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\');
    }

    public function get($key, $default='')
    {
        if (!$key){
            return $default;
        }

        $config = $this->config;
        $items = explode('.', $key);

        if (!$items) {
            return $default;
        }

        foreach ($items as $item) {
            if (!$config[$item]) {
                return $default;
            }
            $config = $config[$item];
        }

        return $config;
    }
}
