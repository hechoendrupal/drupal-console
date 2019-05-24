<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\EntityConfigGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class EntityConfigGenerator extends Generator
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

        $moduleInstance = $this->extensionManager->getModule($module);
        $moduleDir = $moduleInstance->getPath();
        $modulePath = $moduleDir . '/' . $module;
        $moduleSourcePath = $moduleInstance->getSourcePath() . '/' . $entity_class;
        $moduleFormPath = $moduleInstance->getFormPath() . '/' . $entity_class;
        $moduleEntityPath = $moduleInstance->getEntityPath() . '/' . $entity_class;

        $this->renderFile(
            'module/config/schema/entity.schema.yml.twig',
            $moduleDir . '/config/schema/' . $entity_name . '.schema.yml',
            $parameters
        );

        $this->renderFile(
            'module/links.menu-entity-config.yml.twig',
            $modulePath . '.links.menu.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/links.action-entity.yml.twig',
            $modulePath . '.links.action.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/Entity/interface-entity.php.twig',
            $moduleEntityPath . 'Interface.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/entity.php.twig',
            $moduleEntityPath . '.php',
            $parameters
        );

        $this->renderFile(
            'module/src/entity-route-provider.php.twig',
            $moduleSourcePath . 'HtmlRouteProvider.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Form/entity.php.twig',
            $moduleFormPath . 'Form.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Form/entity-delete.php.twig',
            $moduleFormPath . 'DeleteForm.php',
            $parameters
        );

        $this->renderFile(
            'module/src/entity-listbuilder.php.twig',
            $moduleSourcePath . 'ListBuilder.php',
            $parameters
        );
    }
}
