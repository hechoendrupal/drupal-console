<?php

/**
 * @file
 * Contains Drupal\Console\Generator\FormGenerator.
 */

namespace Drupal\Console\Generator;

class FormGenerator extends Generator
{
    /**
     * @param  $module
     * @param  $class_name
     * @param  $services
     * @param  $inputs
     * @param  $form_id
     * @param  $form_type
     * @param  $update_routing
     */
    public function generate($module, $class_name, $form_id, $form_type, $services, $inputs, $update_routing)
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

        if ($form_type == 'ConfigFormBase') {
            $template = 'module/src/Form/form-config.php.twig';
            if ($update_routing) {
                $this->renderFile(
                    'module/routing-form.yml.twig',
                    $this->getSite()->getModulePath($module).'/'.$module.'.routing.yml',
                    $parameters,
                    FILE_APPEND
                );
            }
        } else {
            $template = 'module/src/Form/form.php.twig';
        }

        $this->renderFile(
            $template,
            $this->getSite()->getFormPath($module).'/'.$class_name.'.php',
            $parameters
        );
    }
}
