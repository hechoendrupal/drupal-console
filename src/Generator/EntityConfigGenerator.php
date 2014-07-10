<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Generator\EntityGenerator.
 */

namespace Drupal\AppConsole\Generator;

class EntityConfigGenerator extends Generator
{
  /**
   * Generator Entity
   *
   * @param string $module       Module name
   * @param string $entity_name  Entity machine name
   * @param string $entity_class Entity class name
   */
  public function generate($module, $entity_name, $entity_class)
  {

    $parameters = [
      'module' => $module,
      'entity_name' => $entity_name,
      'entity_class' => $entity_class,
    ];

    $this->renderFile(
      'module/config/schema/entity.schema.yml.twig',
      $this->getModulePath($module) . '/config/schema/' . $entity_name . '.schema.yml',
      $parameters
    );

    $this->renderFile(
      'module/routing-entity.yml.twig',
      $this->getModulePath($module) . '/' . $module . '.routing.yml',
      $parameters,
      FILE_APPEND
    );

    $this->renderFile(
      'module/local_actions-entity.yml.twig',
      $this->getModulePath($module) . '/' . $module . '.local_actions.yml',
      $parameters,
      FILE_APPEND
    );

    $this->renderFile(
      'module/src/interface-entity.php.twig',
      $this->getSourcePath($module) . '/' . $entity_class .'Interface.php',
      $parameters
    );

    $this->renderFile(
      'module/src/Entity/entity.php.twig',
      $this->getEntityPath($module) . '/' . $entity_class . '.php',
      $parameters
    );

    $this->renderFile(
      'module/src/Form/entity.php.twig',
      $this->getFormPath($module) . '/' . $entity_class . 'Form.php',
      $parameters
    );

    $this->renderFile(
      'module/src/Form/entity-delete.php.twig',
      $this->getFormPath($module) . '/' . $entity_class . 'DeleteForm.php',
      $parameters
    );

    $this->renderFile(
      'module/src/Controller/entity-listbuilder.php.twig',
      $this->getControllerPath($module) . '/' . $entity_class . 'ListBuilder.php',
      $parameters
    );
  }
}
