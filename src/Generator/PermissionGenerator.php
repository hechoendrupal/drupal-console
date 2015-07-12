<?php

/**
 * @file
 * Contains Drupal\AppConsole\Generator\PermissionGenerator.
 */

namespace Drupal\AppConsole\Generator;

class PermissionGenerator extends Generator
{
    /**
     * @param  $module
     * @param  $permission
     */
    public function generate($module, $permissions)
    {
        $parameters = array(
          'module_name' => $module,
          'permissions' => $permissions,
        );

        $this->renderFile(
            'module/permission.yml.twig',
            $this->getModulePath($module).'/'.$module.'.permissions.yml',
            $parameters,
            FILE_APPEND
        );

        $content = $this->renderView(
            'module/permission-routing.yml.twig',
            $parameters
        );

        if ($this->isLearning()) {
            echo 'You can use this permission in the routing file like this:'.PHP_EOL;
            echo $content;
        }
    }
}
