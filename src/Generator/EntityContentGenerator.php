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
      'module/routing-content-entity.yml.twig',
      $this->getModulePath($module).'/'.$module.'.routing.yml',
      $parameters,
      FILE_APPEND
    );
      
    $this->renderFile(
      'module/menu_links-content-entity.yml.twig',
      $this->getModulePath($module).'/'.$module.'.menu_links.yml',
      $parameters,
      FILE_APPEND
    );
      
    $this->renderFile(
      'module/local_tasks-content-entity.yml.twig',
      $this->getModulePath($module).'/'.$module.'.local_tasks.yml',
      $parameters,
      FILE_APPEND
    );

    $this->renderFile(
      'module/local_actions-content-entity.yml.twig',
      $this->getModulePath($module).'/'.$module.'.local_actions.yml',
      $parameters,
      FILE_APPEND
    );
      
    $this->renderFile(
      'module/src/interface-content-entity.php.twig',
      $this->getSourcePath($module).'/'.$entity_class.'Interface.php',
      $parameters
    );  
      
    $this->renderFile(
      'module/src/accessController-content-entity.php.twig',
      $this->getSourcePath($module).'/'.$entity_class.'AccessController.php',
      $parameters
    );
      
    $this->renderFile(
      'module/src/Entity/content-entity.php.twig',
      $this->getEntityPath($module).'/'.$entity_class.'.php',
      $parameters
    );
     
    $this->renderFile(
      'module/src/Entity/Controller/listController-content-entity.php.twig',
      $this->getEntityPath($module).'/Controller/'.$entity_class.'ListController.php',
      $parameters
    );
    
    $this->renderFile(
      'module/src/Form/content-entity-settingsForm.php.twig',
      $this->getFormPath($module).'/'.$entity_class.'SettingsForm.php',
      $parameters
    );
      
    $this->renderFile(
      'module/src/Form/content-entity-form.php.twig',
      $this->getFormPath($module).'/'.$entity_class.'Form.php',
      $parameters
    );
      
    $this->renderFile(
      'module/src/Form/content-entity-deleteForm.php.twig',
      $this->getFormPath($module).'/'.$entity_class.'DeleteForm.php',
      $parameters
    );
  }
}
