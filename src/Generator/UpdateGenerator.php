<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\UpdateGenerator.
 */

namespace Drupal\Console\Generator;

class UpdateGenerator extends Generator
{
    /**
     * Generator Update N function.
     *
     * @param $module
     * @param $update_number
     */
    public function generate($module, $update_number)
    {
        $parameters = [
          'module' => $module,
          'update_number' => $update_number,
        ];

        $module_path =  $this->getSite()->getModulePath($module);

        $this->renderFile(
            'module/src/update.php.twig',
            $module_path .'/'.$module.'.module',
            $parameters,
            FILE_APPEND
        );
    }
}
