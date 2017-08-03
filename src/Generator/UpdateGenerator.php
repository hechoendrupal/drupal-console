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
     * Generator Update N function.
     *
     * @param $module
     * @param $update_number
     */
    public function generate($module, $update_number)
    {
        $modulePath =  $this->extensionManager->getModule($module)->getPath();
        $updateFile = $modulePath .'/'.$module.'.install';

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
