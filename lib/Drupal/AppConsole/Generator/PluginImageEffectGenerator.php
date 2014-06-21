<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Generator\PluginImageEffectGenerator.
 */

namespace Drupal\AppConsole\Generator;

class PluginImageEffectGenerator extends Generator
{
  /**
   * Generator Plugin Block
   * @param  string $module   Module name
   * @param  string $class_name     class name for plugin block
   * @param  string $description
   */
  public function generate($module, $class_name, $plugin_label, $plugin_id, $description)
  {
    $path = DRUPAL_ROOT . '/' . drupal_get_path('module', $module);
    $path_plugin = $path . '/src/Plugin/ImageEffect';

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
      $path_plugin . '/'. $class_name .'.php',
      $parameters
    );
  }

}
