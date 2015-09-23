<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginBlockGenerator.
 */

namespace Drupal\Console\Generator;

class FormAlterGenerator extends Generator
{
    /**
     * Generator Plugin Block.
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $services
     */
    public function generate($module, $form_id, $inputs)
    {
        $parameters = [
          'module' => $module,
          'form_id' => $form_id,
          'inputs' => $inputs,
        ];

        $module_path =  $this->getSite()->getModulePath($module);

        $this->renderFile(
            'module/src/Form/form-alter.php.twig',
            $module_path .'/'.$module.'.module',
            $parameters,
            FILE_APPEND
        );
    }
}
