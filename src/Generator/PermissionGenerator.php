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
   * @param  $permission_uc
   */
  public function generate($module, $permission, $permission_uc)
  {
    $parameters = array(
      'module_name' => $module,
      'permissions' => $permission,
      'permission_uc' => $permission_uc,
    );

    $this->renderFile(
      'module/permission.yml.twig',
      $this->getModulePath($module).'/'.$module.'.permissions.yml',
      $parameters
    );

//    if ($update_routing) {
//      $this->renderFile(
//        'module/permission.yml.twig',
//        $this->getModulePath($module).'/'.$module.'.permissions.yml',
//        $parameters,
//        FILE_APPEND
//      );
//    }
  }
}
