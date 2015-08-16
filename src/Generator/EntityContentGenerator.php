<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Generator\EntityContentGenerator.
 */

namespace Drupal\AppConsole\Generator;

class EntityContentGenerator extends Generator
{
    /**
     * Generator Entity.
     *
     * @param string $module       Module name
     * @param string $entity_name  Entity machine name
     * @param string $entity_class Entity class name
     * @param string $label        Entity label
     */
    public function generate($module, $entity_name, $entity_class, $label)
    {
        $parameters = [
          'module' => $module,
          'entity_name' => $entity_name,
          'entity_class' => $entity_class,
            'label' => $label,
        ];

        $this->renderFile(
            'module/routing-entity-content.yml.twig',
            $this->getSite()->getModulePath($module).'/'.$module.'.routing.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/permissions-entity-content.yml.twig',
            $this->getSite()->getModulePath($module).'/'.$module.'.permissions.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/links.menu-entity-content.yml.twig',
            $this->getSite()->getModulePath($module).'/'.$module.'.links.menu.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/links.task-entity-content.yml.twig',
            $this->getSite()->getModulePath($module).'/'.$module.'.links.task.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/links.action-entity-content.yml.twig',
            $this->getSite()->getModulePath($module).'/'.$module.'.links.action.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/interface-entity-content.php.twig',
            $this->getSite()->getSourcePath($module).'/'.$entity_class.'Interface.php',
            $parameters
        );

        $this->renderFile(
            'module/src/accesscontrolhandler-entity-content.php.twig',
            $this->getSite()->getSourcePath($module).'/'.$entity_class.'AccessControlHandler.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/entity-content.php.twig',
            $this->getSite()->getEntityPath($module).'/'.$entity_class.'.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/entity-content-views-data.php.twig',
            $this->getSite()->getEntityPath($module).'/'.$entity_class.'ViewsData.php',
            $parameters
        );

        $this->renderFile(
            'module/src/listbuilder-entity-content.php.twig',
            $this->getSite()->getSourcePath($module).'/'.$entity_class.'ListBuilder.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/Form/entity-settings.php.twig',
            $this->getSite()->getEntityPath($module).'/Form/'.$entity_class.'SettingsForm.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/Form/entity-content.php.twig',
            $this->getSite()->getEntityPath($module).'/Form/'.$entity_class.'Form.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/Form/entity-content-delete.php.twig',
            $this->getSite()->getEntityPath($module).'/Form/'.$entity_class.'DeleteForm.php',
            $parameters
        );

        $this->renderFile(
            'module/entity-content-page.php.twig',
            $this->getSite()->getModulePath($module).'/'.$entity_name.'.page.inc',
            $parameters
        );

        $this->renderFile(
            'module/templates/entity-html.twig',
            $this->getSite()->getTemplatePath($module).'/'.$entity_name.'.html.twig',
            $parameters
        );

        $content = $this->renderView(
            'module/src/Entity/entity-content.theme.php.twig',
            $parameters
        );

        echo 'Add this to your hook_theme:'.PHP_EOL;
        echo $content;
    }
}
