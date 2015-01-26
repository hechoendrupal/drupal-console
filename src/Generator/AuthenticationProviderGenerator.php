<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Generator\AuthenticationProviderGenerator.
 */

namespace Drupal\AppConsole\Generator;

class AuthenticationProviderGenerator extends Generator
{
  /**
   * Generator Plugin Block
   * @param  $module
   * @param  $class_name
   */
  public function generate($module, $class_name)
  {
    $parameters = [
      'module'   => $module,
      'class_name'   => $class_name
    ];

    $this->renderFile(
      'module/src/Authentication/Provider/authentication_provider.php.twig',
      $this->getAuthenticationPath($module, 'Provider').'/'.$class_name.'.php',
      $parameters
    );

    $parameters['class_name'] = "Authentication\Provider\\" . $class_name;
    $parameters['service_name'] = 'authentication.' . $module;
    $parameters['services'] = array(array('name' => 'config.factory'), array('name' => 'entity.manager'));
    $parameters['file_exists'] = file_exists($this->getModulePath($module).'/'.$module.'.services.yml');
    $parameters['tags'] = array(array( 'name' => 'authentication_provider', 'priority' => 100));

    $this->renderFile(
      'module/services.yml.twig',
      $this->getModulePath($module).'/'.$module.'.services.yml',
      $parameters,
      FILE_APPEND
    );
  }
}
