<?php

/**
 * @file
 * Contains \Drupal\Console\Config.
 */

namespace Drupal\Console;

use Symfony\Component\Yaml\Parser;

/**
 * Class Config
 * @package Drupal\Console
 */
class Config
{
    /**
     * @var array
     */
    protected $config = [];

    public function __construct()
    {
        $this->config = [];

        $this->loadFile(__DIR__.'/../config.yml');
        $this->loadFile($this->getUserHomeDir().'/.console/config.yml');
        $this->loadFile(__DIR__.'/../config/dist/aliases.yml');
        $this->loadFile($this->getUserHomeDir().'/.console/aliases.yml');
    }

    /**
   * @param $file
   * @return array
   */
    public function getFileContents($file)
    {
        if (file_exists($file)) {
            $parser = new Parser();
            return $parser->parse(file_get_contents($file));
        }

        return [];
    }

    /**
     * @param string|null $file
     * @param string|null $prefix
     *
     * @return bool
     */
    private function loadFile($file = null, $prefix=null)
    {
        $config = $this->getFileContents($file);

        if ($config) {
            if ($prefix) {
                $prefixes = explode('.', $prefix);
                $this->setResourceArray($prefixes, $this->config, $config);
            } else {
                $this->config = array_replace_recursive($this->config, $config);
            }
            return true;
        }

        return false;
    }

    /**
     * @param $parents
     * @param $parentsArray
     * @param $resource
     * @return mixed
     */
    private function setResourceArray($parents, &$parentsArray, $resource)
    {
        $ref = &$parentsArray;
        foreach ($parents as $parent) {
            $ref[$parent] = [];
            $previous = &$ref;
            $ref = &$ref[$parent];
        }

        $previous[$parent] = $resource;
        return $parentsArray;
    }

    /**
     * @param string $key
     * @param string $default
     * @return array|mixed|null|string
     */
    public function get($key, $default = '')
    {
        if (!$key) {
            return $default;
        }

        $config = $this->config;
        $items = explode('.', $key);

        if (!$items) {
            return $default;
        }
        foreach ($items as $item) {
            if (empty($config[$item])) {
                return $default;
            }
            $config = $config[$item];
        }

        return $config;
    }

    /**
     * Return the user home directory.
     *
     * @return string
     */
    public function getUserHomeDir()
    {
        return rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\');
    }

    /**
     * Return the site config directory.
     *
     * @return string
     */
    public function getSitesDirectory()
    {
        return sprintf(
            '%s/.console/sites',
            $this->getUserHomeDir()
        );
    }

    /**
     * @param string $site
     * @return bool
     */
    public function loadSite($site)
    {
        $siteFile = $this->getSitesDirectory()  . '/' . $site . '.yml';
        $prefix = 'sites.'.$site;
        return $this->loadFile($siteFile, $prefix);
    }

    /**
     * @param string $target
     * @return array|mixed|null|\Exception
     */
    public function loadTarget($target)
    {
        $site = null;
        if (strpos($target, '.')) {
            $site = explode('.', $target)[0];
        }

        return $this->loadSite($site);
    }

    /**
   * @param $target
   * @return array|mixed|null|string
   */
    public function getTarget($target)
    {
        $targetConfig = $this->get('sites.' . $target);
        $targetConfig['remote'] = false;
        if (array_key_exists('host', $targetConfig) && $targetConfig['host'] != 'local') {
            $remoteConfig = $this->get('application.remote');
            $targetConfig = array_replace_recursive($remoteConfig, $targetConfig);
            $targetConfig['remote'] = true;
        }
        return $targetConfig;
    }
}
