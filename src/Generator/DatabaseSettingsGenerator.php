<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\DatabaseSettingsGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;

class DatabaseSettingsGenerator extends Generator
{
    /**
     * DatabaseSettingsGenerator constructor.
     */
    public function __construct() {}

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $uri = parse_url($parameters['uri'], PHP_URL_HOST);
        $settingsFile = 'sites/'.$uri.'/settings.php';
        if (!is_writable($settingsFile)) {
            return false;
        }
        $template = 'database/add.php.twig';
        if ($parameters['default']) {
            $template = 'database/add-default.php.twig';
        }
        return $this->renderFile(
            $template,
            $settingsFile,
            $parameters,
            FILE_APPEND
        );
    }
}
