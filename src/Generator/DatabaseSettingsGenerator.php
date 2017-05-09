<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\DatabaseSettingsGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Core\DrupalKernelInterface;

class DatabaseSettingsGenerator extends Generator
{
    /**
     * @var DrupalKernelInterface
     */
    protected $kernel;

    /**
     * DatabaseSettingsGenerator constructor.
     *
     * @param DrupalKernelInterface $kernel
     */
    public function __construct(
        DrupalKernelInterface $kernel
    ) {
        $this->kernel = $kernel;
    }


    /**
     * Generator Plugin Block.
     *
     * @param $parameters
     */
    public function generate($parameters)
    {
        $settingsFile = $this->kernel->getSitePath().'/settings.php';
        if (!is_writable($settingsFile)) {
            return false;
        }
        return $this->renderFile(
            'database/add.php.twig',
            $settingsFile,
            $parameters,
            FILE_APPEND
        );
    }
}
