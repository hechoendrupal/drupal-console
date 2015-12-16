<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\EntityContentGenerator.
 */

namespace Drupal\Console\Generator;

class EntityContentGenerator extends Generator
{
    /**
     * Generator Entity.
     *
     * @param string $module             Module name
     * @param string $entity_name        Entity machine name
     * @param string $entity_class       Entity class name
     * @param string $label              Entity label
     * @param string $bundle_entity_type (Config) entity type acting as bundle
     */
    public function generate($module, $entity_name, $entity_class, $label, $bundle_entity_type = null)
    {
        $parameters = [
            'module' => $module,
            'entity_name' => $entity_name,
            'entity_class' => $entity_class,
            'label' => $label,
            'bundle_entity_type' => $bundle_entity_type,
        ];

        if ($bundle_entity_type) {
            $controller_class = $entity_class . 'AddController';
            $this->renderFile(
                'module/src/Controller/controller-add-page.php.twig',
                $this->getSite()->getControllerPath($module).'/'.$controller_class .'.php',
                $parameters + array(
                    'class_name' => $controller_class,
                    'services' => [],
                )
            );
        }

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
            $this->getSite()->getFormPath($module).'/'.$entity_class.'SettingsForm.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/Form/entity-content.php.twig',
            $this->getSite()->getFormPath($module).'/'.$entity_class.'Form.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/Form/entity-content-delete.php.twig',
            $this->getSite()->getFormPath($module).'/'.$entity_class.'DeleteForm.php',
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

        if ($bundle_entity_type) {
            $this->renderFile(
                'module/templates/entity-with-bundle-content-add-list-html.twig',
                $this->getSite()->getTemplatePath($module).'/'.str_replace('_', '-', $entity_name).'-content-add-list.html.twig',
                $parameters
            );

            // Check for hook_theme() in module file and warn ...
            $module_filename = $this->getSite()->getModulePath($module).'/'.$module.'.module';
            $module_file_contents = file_get_contents($module_filename);
            if (strpos($module_file_contents, 'function ' . $module . '_theme') !== false) {
                echo "================\nWarning:\n================\n" .
                  "It looks like you have a hook_theme already declared!\n".
                  "Please manually merge the two hook_theme() implementations in $module_filename!\n";
            }

            $this->renderFile(
                'module/src/Entity/entity-content-with-bundle.theme.php.twig',
                $this->getSite()->getModulePath($module).'/'.$module.'.module',
                $parameters,
                FILE_APPEND
            );
        }

        $content = $this->getRenderHelper()->render(
            'module/src/Entity/entity-content.theme.php.twig',
            $parameters
        );

        if ($this->isLearning()) {
            echo 'Add this to your hook_theme:'.PHP_EOL;
            echo $content;
        }
    }
}
