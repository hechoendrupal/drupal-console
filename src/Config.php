<?php

/**
 * @file
 */

namespace Drupal\AppConsole;

use Symfony\Component\Yaml\Parser;

class Config {

    protected $parser;

    protected $root_path;

    public function __construct(Parser $parser, $root_path)
    {
        $this->Parser = $parser;
        $this->root_path = $root_path;
    }

    protected function readYamlFile($path_file)
    {
        if (file_exists($path_file)) {
            return $this->Parser->parse(file_get_contents($path_file));
        }
        else {
            return [];
        }
    }

    public function getUserHomeDir()
    {
        return rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\');
    }

    public function getBaseConfig()
    {
        return $this->readYamlFile($this->root_path . '/config.yml');
    }

    public function getUserConfig()
    {
        $userConfig = $this->readYamlFile(
            $this->getUserHomeDir() . '/.console/config.yml'
        );

        unset($userConfig['application']['name']);
        unset($userConfig['application']['version']);

        return $userConfig;
    }

    public function getConfig()
    {
        $baseConfig = $this->getBaseConfig();
        $userConfig = $this->getUserConfig();

        return array_replace_recursive($baseConfig, $userConfig);
    }
}
