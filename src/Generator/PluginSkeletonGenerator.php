<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginSkeletonGenerator.
 */

namespace Drupal\Console\Generator;

class PluginSkeletonGenerator extends Generator
{
    /**
     * Generator Post Update Name function.
     *
     * @param $module
     * @param $post_update_name
     */
    public function generate($module, $pluginId)
    {
        $module_path =  $this->getSite()->getModulePath($module);

        $parameters = [
          'machine_name' => $module,
          'description' => $pluginId,
          'file_exists' => file_exists($module_path .'/'.$module.'.module'),
        ];

        $this->renderFile(
            'module/src/plugin-skeleton.php.twig',
            $module_path .'/'.$module.'.module',
            $parameters,
            FILE_APPEND
        );
    }
}
