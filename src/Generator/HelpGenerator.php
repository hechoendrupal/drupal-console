<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\HelpGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class HelpGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * HelpGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Generator Post Update Name function.
     *
     * @param $module
     * @param $post_update_name
     */
    public function generate($module, $description)
    {
        $module_path =  $this->extensionManager->getModule($module)->getPath();

        $parameters = [
          'machine_name' => $module,
          'description' => $description,
          'file_exists' => file_exists($module_path .'/'.$module.'.module'),
        ];

        $this->renderFile(
            'module/src/help.php.twig',
            $module_path .'/'.$module.'.module',
            $parameters,
            FILE_APPEND
        );
    }
}
