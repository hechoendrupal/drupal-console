<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Generator\EntityContentGenerator.
 */

namespace Drupal\AppConsole\Generator;

class EntityContentGenerator extends Generator
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
      'module/routing-entity-content.yml.twig',
      $this->getModulePath($module).'/'.$module.'.routing.yml',
      $parameters,
      FILE_APPEND
    );
      
    $this->renderFile(
      'module/menu_links-entity.yml.twig',
      $this->getModulePath($module).'/'.$module.'.menu_links.yml',
      $parameters,
      FILE_APPEND
    );
      
    $this->renderFile(
      'module/local_tasks-entity.yml.twig',
      $this->getModulePath($module).'/'.$module.'.local_tasks.yml',
      $parameters,
      FILE_APPEND
    );

    $this->renderFile(
      'module/local_actions-entity-content.yml.twig',
      $this->getModulePath($module).'/'.$module.'.local_actions.yml',
      $parameters,
      FILE_APPEND
    );
      
    $this->renderFile(
      'module/src/interface-entity-content.php.twig',
      $this->getSourcePath($module).'/'.$entity_class.'Interface.php',
      $parameters
    );
      
    $this->renderFile(
      'module/src/accessController-entity-content.php.twig',
      $this->getSourcePath($module).'/'.$entity_class.'AccessController.php',
      $parameters
    );
      
    $this->renderFile(
      'module/src/Entity/entity-content.php.twig',
      $this->getEntityPath($module).'/'.$entity_class.'.php',
      $parameters
    );
     
    $this->renderFile(
      'module/src/Entity/Controller/listController-entity-content.php.twig',
      $this->getEntityPath($module).'/Controller/'.$entity_class.'ListController.php',
      $parameters
    );
    
    $this->renderFile(
      'module/src/Form/entity-settings.php.twig',
      $this->getFormPath($module).'/'.$entity_class.'SettingsForm.php',
      $parameters
    );
      
    $this->renderFile(
      'module/src/Form/entity-content.php.twig',
      $this->getFormPath($module).'/'.$entity_class.'Form.php',
      $parameters
    );
      
    $this->renderFile(
      'module/src/Form/entity-content-delete.php.twig',
      $this->getFormPath($module).'/'.$entity_class.'DeleteForm.php',
      $parameters
    );
  }
}
