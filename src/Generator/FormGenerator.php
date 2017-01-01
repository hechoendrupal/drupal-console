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
     * @param  $module
     * @param  $class_name
     * @param  $services
     * @param  $inputs
     * @param  $form_id
     * @param  $form_type
     * @param  $path
     * @param  $menu_link_gen
     * @param  $menu_link_title
     * @param  $menu_parent
     * @param  $menu_link_desc
     */
    public function generate($module, $class_name, $form_id, $form_type, $services, $inputs, $path, $menu_link_gen, $menu_link_title, $menu_parent, $menu_link_desc)
    {
        $class_name_short = strtolower(
            $this->stringConverter->removeSuffix($class_name)
        );

        $parameters = [
          'class_name' => $class_name,
          'services' => $services,
          'inputs' => $inputs,
          'module_name' => $module,
          'form_id' => $form_id,
          'path' => $path,
          'route_name' => $class_name,
          'menu_link_title' => $menu_link_title,
          'menu_parent' => $menu_parent,
          'menu_link_desc' => $menu_link_desc,
          'class_name_short'  => $class_name_short
        ];

        if ($form_type == 'ConfigFormBase') {
            $template = 'module/src/Form/form-config.php.twig';
            $parameters['config_form'] = true;
        } else {
            $template = 'module/src/Form/form.php.twig';
            $parameters['config_form'] = false;
        }

        $this->renderFile(
            'module/routing-form.yml.twig',
            $this->extensionManager->getModule($module)->getPath() .'/'.$module.'.routing.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            $template,
            $this->extensionManager->getModule($module)->getFormPath() .'/'.$class_name.'.php',
            $parameters
        );

        // Render defaults YML file.
        $this->renderFile(
            'module/config/install/field.default.yml.twig',
            $this->extensionManager->getModule($module)->getPath() .'/config/install/'.$module.'.'.$class_name_short.'.yml',
            $parameters
        );

        if ($menu_link_gen == true) {
            $this->renderFile(
                'module/links.menu.yml.twig',
                $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.links.menu.yml',
                $parameters,
                FILE_APPEND
            );
        }
    }
}
