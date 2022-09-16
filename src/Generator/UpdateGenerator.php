<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\UpdateGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class UpdateGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $module = $parameters['module'];
        $update_number = $parameters['update_number'];
        $updateFile = $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.install';

        $parameters = [
          'module' => $module,
          'update_number' => $update_number,
          'file_exists' => file_exists($updateFile)
        ];

        $this->renderFile(
            'module/update.php.twig',
            $updateFile,
            $parameters,
            FILE_APPEND
        );
    }
}
