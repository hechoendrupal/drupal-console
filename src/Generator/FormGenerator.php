<?php

/**
 * @file
 * Contains Drupal\AppConsole\Generator\FormGenerator.
 */
namespace Drupal\AppConsole\Generator;

class FormGenerator extends Generator
{
    /**
     * @param  $module
     * @param  $class_name
     * @param  $services
     * @param  $inputs
     * @param  $form_id
     * @param  $update_routing
     */
    public function generate($module, $class_name, $form_id, $services, $inputs, $update_routing)
    {
        $class_name_short = substr($class_name, -4) == 'Form' ? str_replace('Form', '', $class_name) : $class_name;
        $parameters = array(
          'class_name' => $class_name,
          'services' => $services,
          'inputs' => $inputs,
          'module_name' => $module,
          'form_id' => $form_id,
          'class_name_short' => strtolower($class_name_short),
        );

        $this->renderFile(
          'module/src/Form/form.php.twig',
          $this->getFormPath($module).'/'.$class_name.'.php',
          $parameters
        );

        if ($update_routing) {
            $this->renderFile(
              'module/routing-form.yml.twig',
              $this->getModulePath($module).'/'.$module.'.routing.yml',
              $parameters,
              FILE_APPEND
            );
        }
    }
}
