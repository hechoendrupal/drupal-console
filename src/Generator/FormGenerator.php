<?php

/**
 * @file
 * Contains Drupal\Console\Generator\FormGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;

class FormGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * AuthenticationProviderGenerator constructor.
     *
     * @param Manager         $extensionManager
     * @param StringConverter $stringConverter
     */
    public function __construct(
        Manager $extensionManager,
        StringConverter $stringConverter
    ) {
        $this->extensionManager = $extensionManager;
        $this->stringConverter = $stringConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $class_name = $parameters['class_name'];
        $form_type = $parameters['form_type'];
        $module = $parameters['module_name'];
        $config_file = $parameters['config_file'];
        $menu_link_gen = $parameters['menu_link_gen'];

        $moduleInstance = $this->extensionManager->getModule($module);
        $moduleDir = $moduleInstance->getPath();
        $modulePath = $moduleDir . '/' . $module;

        $class_name_short = strtolower(
            $this->stringConverter->removeSuffix($class_name)
        );

        $parameters = array_merge($parameters, [
          'class_name_short' => $class_name_short
        ]);

        if ($form_type == 'ConfigFormBase') {
            $template = 'module/src/Form/form-config.php.twig';
            $parameters['config_form'] = true;
        } else {
            $template = 'module/src/Form/form.php.twig';
            $parameters['config_form'] = false;
        }

        $this->renderFile(
            'module/routing-form.yml.twig',
          $modulePath . '.routing.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            $template,
            $moduleInstance->getFormPath() . '/' . $class_name . '.php',
            $parameters
        );

        // Render defaults YML file.
        if ($config_file == true) {
            $this->renderFile(
                'module/config/install/field.default.yml.twig',
              $moduleDir . '/config/install/' . $module . '.' . $class_name_short . '.yml',
                $parameters
            );
        }

        if ($menu_link_gen == true) {
            $this->renderFile(
                'module/links.menu.yml.twig',
              $modulePath . '.links.menu.yml',
                $parameters,
                FILE_APPEND
            );
        }
    }
}
