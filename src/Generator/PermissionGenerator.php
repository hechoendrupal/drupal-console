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
     * @param  $permission_title
     */
    public function generate($module, $permission, $permission_title)
    {
        $parameters = array(
          'module_name' => $module,
          'permissions' => $permission,
          'permission_title' => $permission_title,
        );

        $this->renderFile(
          'module/permission.yml.twig',
          $this->getModulePath($module) . '/' . $module . '.permissions.yml',
          $parameters
        );
    }
}
