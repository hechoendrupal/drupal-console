<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Generator\EntityGenerator.
 */

namespace Drupal\AppConsole\Generator;

class EntityGenerator extends Generator
{
  /**
   * Generator Service
   * @param  string $module       Module name
   * @param  string $service_name Service name
   * @param  string $class_name   Class name
   * @param  array  $services     List of services
   */
  public function generate($module, $entity)
  {

    $parameters = [
      'module' => $module,
      'entity' => $entity
    ];

    $this->renderFile(
      'module/config/schema/entity.schema.yml.twig',
      $this->getModulePath($module). '/config/schema/' . $entity . '.schema.yml',
      $parameters
    );

    $this->renderFile(
      'module/routing-entity.yml.twig',
      $this->getModulePath($module).'/'.$module.'.routing.yml',
      $parameters,
      FILE_APPEND
    );

    $this->renderFile(
      'module/local_actions-entity.yml.twig',
      $this->getModulePath($module).'/'.$module.'.local_actions.yml',
      $parameters,
      FILE_APPEND
    );

    $this->renderFile(
      'module/interface-entity.php.twig',
      $this->getSourcePath($module).'/'.ucwords($entity).'Interface.php',
      $parameters
    );

    $this->renderFile(
      'module/Entity/entity.php.twig',
      $this->getEntityPath($module).'/'.ucwords($entity).'.php',
      $parameters
    );

    $this->renderFile(
      'module/Form/entity.php.twig',
      $this->getFormPath($module).'/'.ucwords($entity).'Form.php',
      $parameters
    );

    $this->renderFile(
      'module/Form/entity-delete.php.twig',
      $this->getFormPath($module).'/'.ucwords($entity).'DeleteForm.php',
      $parameters
    );

    $this->renderFile(
      'module/Controller/entity-listbuilder.php.twig',
      $this->getControllerPath($module).'/'.ucwords($entity).'ListBuilder.php',
      $parameters
    );
  }
}
