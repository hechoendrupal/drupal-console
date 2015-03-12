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
    public function generate($module, $permission, $title, $description, $restrictAccess)
    {
        $parameters = array(
          'module_name' => $module,
          'permissions' => $permission,
          'title' => $title,
          'description' => $description,
          'restrict_access' => $restrictAccess
        );

        $this->renderFile(
          'module/permission.yml.twig',
          $this->getModulePath($module) . '/' . $module . '.permissions.yml',
          $parameters,
          FILE_APPEND
        );
    }
}
