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
     * @param  $permissions
     * @param  $permission_title
     */
    public function generate($module, $permissions, $permission_title)
    {
        $parameters = array(
          'module_name' => $module,
          'permissions' => $permissions,
          'permission_title' => $permission_title,
        );

        $this->renderFile(
          'module/permission.yml.twig',
          $this->getModulePath($module) . '/' . $module . '.permissions.yml',
          $parameters
        );
    }
}
