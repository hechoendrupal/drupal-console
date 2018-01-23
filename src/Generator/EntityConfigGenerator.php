<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\EntityConfigGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Generator\GeneratorInterface;

class EntityConfigGenerator extends Generator implements GeneratorInterface
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * EntityConfigGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }


    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $module = $parameters['module'];
        $entity_name = $parameters['entity_name'];
        $entity_class = $parameters['entity_class'];

        $this->renderFile(
            'module/config/schema/entity.schema.yml.twig',
            $this->extensionManager->getModule($module)->getPath() . '/config/schema/'.$entity_name.'.schema.yml',
            $parameters
        );

        $this->renderFile(
            'module/links.menu-entity-config.yml.twig',
            $this->extensionManager->getModule($module)->getPath() . '/' . $module.'.links.menu.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/links.action-entity.yml.twig',
            $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.links.action.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/Entity/interface-entity.php.twig',
            $this->extensionManager->getModule($module)->getEntityPath() . '/' . $entity_class . 'Interface.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/entity.php.twig',
            $this->extensionManager->getModule($module)->getEntityPath() . '/' . $entity_class . '.php',
            $parameters
        );

        $this->renderFile(
            'module/src/entity-route-provider.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath() . '/' . $entity_class.'HtmlRouteProvider.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Form/entity.php.twig',
            $this->extensionManager->getModule($module)->getFormPath() . '/' . $entity_class . 'Form.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Form/entity-delete.php.twig',
            $this->extensionManager->getModule($module)->getFormPath() . '/' . $entity_class . 'DeleteForm.php',
            $parameters
        );

        $this->renderFile(
            'module/src/entity-listbuilder.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath() . '/' . $entity_class . 'ListBuilder.php',
            $parameters
        );
    }
}
