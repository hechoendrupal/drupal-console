<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\UpdateGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Extension\Manager;

class UpdateGenerator extends Generator
{
    /**
     * @var Manager  
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
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
        $parameters = [
          'module' => $module,
          'update_number' => $update_number,
        ];

        $module_path =  $this->extensionManager->getModule($module)->getPath();

        $this->renderFile(
            'module/src/update.php.twig',
            $module_path .'/'.$module.'.install',
            $parameters,
            FILE_APPEND
        );
    }
}
