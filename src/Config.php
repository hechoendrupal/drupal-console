<?php

/**
 * @file
 * Contains \Drupal\Console\Config.
 */

namespace Drupal\Console;

use Symfony\Component\Yaml\Parser;

class Config
{
    protected $file;

    protected $parser;

    protected $config;

    public function __construct($file = null)
    {
        $this->parser = new Parser();
        if ($file) {
            $this->file = $file;
            $this->config = $this->readYamlFile($file);
        }
    }

    /**
     * @param string|null $file
     * @return array|mixed|null
     */
    public function readYamlFile($file = null)
    {
        if (is_null($file)) {
            return [];
        }

        if (file_exists($file)) {
            return $this->parser->parse(file_get_contents($file));
        } else {
            return [];
        }
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
}
