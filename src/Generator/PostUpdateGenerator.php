<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PostUpdateGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PostUpdateGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PostUpdateGenerator constructor.
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
    public function generate($module, $post_update_name)
    {
        $module_path =  $this->extensionManager->getModule($module)->getPath();

        $parameters = [
          'module' => $module,
          'post_update_name' => $post_update_name,
          'file_exists' => file_exists($module_path .'/'.$module.'.post_update.php'),
        ];

        $this->renderFile(
            'module/post-update.php.twig',
            $module_path .'/'.$module.'.post_update.php',
            $parameters,
            FILE_APPEND
        );
    }
}
