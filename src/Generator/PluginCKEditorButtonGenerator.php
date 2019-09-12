<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginCKEditodButtonGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginCKEditorButtonGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PermissionGenerator constructor.
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
        $class_name = $parameters['class_name'];
        $module = $parameters['module'];
        $plugin_id = $parameters['plugin_id'];
        
        $this->renderFile(
            'module/src/Plugin/CKEditorPlugin/ckeditorbutton.php.twig',
            $this->extensionManager->getPluginPath($module, 'CKEditorPlugin') . '/' . $class_name . '.php',
            $parameters
        );
        $this->renderFile(
            'module/src/Plugin/CKEditorPlugin/plugin.php.twig',
             drupal_get_path('module', $module) . '/js/Plugin/'. $plugin_id .'/plugin.js',
             $parameters
        );
    }
}
