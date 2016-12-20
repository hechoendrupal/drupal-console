<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\EntityConfigGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Extension\Manager;

class EntityConfigGenerator extends Generator
{
    /**
     * @var Manager  
     */
    protected $extensionManager;

    /**
     * EntityConfigGenerator constructor.
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }


    /**
     * Generator Entity.
     *
     * @param string $module       Module name
     * @param string $entity_name  Entity machine name
     * @param string $entity_class Entity class name
     * @param string $label        Entity label
     * @param string $base_path    Base path
     * @param string $bundle_of    Entity machine name of the content entity this config entity acts as a bundle for.
     */
    public function generate($module, $entity_name, $entity_class, $label, $base_path, $bundle_of = null)
    {
        $parameters = [
          'module' => $module,
          'entity_name' => $entity_name,
          'entity_class' => $entity_class,
          'label' => $label,
          'bundle_of' => $bundle_of,
          'base_path' => $base_path,
        ];

        $this->renderFile(
            'module/config/schema/entity.schema.yml.twig',
            $this->extensionManager->getModule($module)->getPath().'/config/schema/'.$entity_name.'.schema.yml',
            $parameters
        );

        $this->renderFile(
            'module/links.menu-entity-config.yml.twig',
            $this->extensionManager->getModule($module)->getPath().'/'.$module.'.links.menu.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/links.action-entity.yml.twig',
            $this->extensionManager->getModule($module)->getPath().'/'.$module.'.links.action.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/Entity/interface-entity.php.twig',
            $this->extensionManager->getModule($module)->getEntityPath().'/'.$entity_class.'Interface.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/entity.php.twig',
            $this->extensionManager->getModule($module)->getEntityPath().'/'.$entity_class.'.php',
            $parameters
        );

        $this->renderFile(
            'module/src/entity-route-provider.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath().'/'.$entity_class.'HtmlRouteProvider.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Form/entity.php.twig',
            $this->extensionManager->getModule($module)->getFormPath().'/'.$entity_class.'Form.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Form/entity-delete.php.twig',
            $this->extensionManager->getModule($module)->getFormPath().'/'.$entity_class.'DeleteForm.php',
            $parameters
        );

        $this->renderFile(
            'module/src/entity-listbuilder.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath().'/'.$entity_class.'ListBuilder.php',
            $parameters
        );
    }
}
