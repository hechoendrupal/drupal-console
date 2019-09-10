<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginFieldFormatterGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginViewsFieldGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginViewsFieldGenerator constructor.
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
        $fields = $parameters['fields'];

        $this->renderFile(
            'module/module.views.inc.twig',
            $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.views.inc',
            $parameters,
            FILE_APPEND
        );

        foreach ($fields as $field) {
            $field['module'] = $module;
            $this->renderFile(
                'module/src/Plugin/Views/field/field.php.twig',
                $this->extensionManager->getPluginPath($module, 'views/field') . '/' . $field['class_name'] . '.php',
                $field
            );
        }
    }
}
