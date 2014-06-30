<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Generator\PluginImageEffectGenerator.
 */

namespace Drupal\AppConsole\Generator;

class PluginImageEffectGenerator extends Generator
{
  /**
   * Generator Plugin Image Effect
   * @param  string $module         Module name
   * @param  string $class_name     Plugin Class name
   * @param  string $plugin_label   Plugin label
   * @param  string $plugin_id      Plugin id
   * @param  string $description    Plugin description
   */
  public function generate($module, $class_name, $plugin_label, $plugin_id, $description)
  {

    $parameters = [
      'module'   => $module,
      'class'     => [
        'name'      => $class_name,
      ],
      'plugin_label' => $plugin_label,
      'plugin_id' => $plugin_id,
      'description' => $description,
    ];

    $this->renderFile(
      'module/plugin-imageeffect.php.twig',
      $this->getPluginPath($module, 'ImageEffect').'/'.$class_name.'.php',
      $parameters
    );
  }
}
