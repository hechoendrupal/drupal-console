<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PostUpdateGenerator.
 */

namespace Drupal\Console\Generator;

class PostUpdateGenerator extends Generator
{
    /**
     * Generator Post Update Name function.
     *
     * @param $module
     * @param $post_update_name
     */
    public function generate($module, $post_update_name)
    {
        $module_path =  $this->getSite()->getModulePath($module);

        $parameters = [
          'module' => $module,
          'post_update_name' => $post_update_name,
          'file_exists' => file_exists($module_path .'/'.$module.'.post_update.php'),
        ];

        $this->renderFile(
            'module/src/post-update.php.twig',
            $module_path .'/'.$module.'.post_update.php',
            $parameters,
            FILE_APPEND
        );
    }
}
