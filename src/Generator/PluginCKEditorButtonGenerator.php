<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginCKEditodButtonGenerator.
 */

namespace Drupal\Console\Generator;

class PluginCKEditorButtonGenerator extends Generator
{
    /**
     * Generator Plugin CKEditor Button.
     *
     * @param string $module      Module name
     * @param string $class_name  Plugin Class name
     * @param string $label       Plugin label
     * @param string $plugin_id   Plugin id
     * @param string $button_name Button name
     */
    public function generate($module, $class_name, $label, $plugin_id, $button_name, $button_icon_path)
    {
        $parameters = [
            'module' => $module,
            'class_name' => $class_name,
            'label' => $label,
            'plugin_id' => $plugin_id,
            'button_name' => $button_name,
            'button_icon_path' => $button_icon_path,
        ];

        $this->renderFile(
            'module/src/Plugin/CKEditorPlugin/ckeditorbutton.php.twig',
            $this->getSite()->getPluginPath($module, 'CKEditorPlugin') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
