<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\HelpGenerator.
 */

namespace Drupal\Console\Generator;

class HelpGenerator extends Generator
{
    /**
     * Generator Post Update Name function.
     *
     * @param $module
     * @param $post_update_name
     */
    public function generate($module, $description)
    {
        $module_path =  $this->getSite()->getModulePath($module);

        $parameters = [
          'machine_name' => $module,
          'description' => $description,
          'file_exists' => file_exists($module_path .'/'.$module.'.module'),
        ];

        $this->renderFile(
            'module/src/help.php.twig',
            $module_path .'/'.$module.'.module',
            $parameters,
            FILE_APPEND
        );
    }
}
