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
     * @param string $base_path          Base path
     * @param string $is_translatable    Translation configuration
     * @param string $bundle_entity_type (Config) entity type acting as bundle
     */
    public function generate($module, $entity_name, $entity_class, $label, $base_path, $is_translatable, $bundle_entity_type = null)
    {
        $parameters = [
            'module' => $module,
            'entity_name' => $entity_name,
            'entity_class' => $entity_class,
            'label' => $label,
            'bundle_entity_type' => $bundle_entity_type,
            'base_path' => $base_path,
            'is_translatable' => $is_translatable,
        ];

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
            'module/src/accesscontrolhandler-entity-content.php.twig',
            $this->getSite()->getSourcePath($module).'/'.$entity_class.'AccessControlHandler.php',
            $parameters
        );

        if ($is_translatable) {
            $this->renderFile(
                'module/src/entity-translation-handler.php.twig',
                $this->getSite()->getSourcePath($module).'/'.$entity_class.'TranslationHandler.php',
                $parameters
            );
        }

        $this->renderFile(
            'module/src/Entity/interface-entity-content.php.twig',
            $this->getSite()->getEntityPath($module).'/'.$entity_class.'Interface.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/entity-content.php.twig',
            $this->getSite()->getEntityPath($module).'/'.$entity_class.'.php',
            $parameters
        );

        $this->renderFile(
            'module/src/entity-content-route-provider.php.twig',
            $this->getSite()->getSourcePath($module).'/'.$entity_class.'HtmlRouteProvider.php',
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
            // Check if the module file exists.
            if (!file_exists($module_filename)) {
                $this->renderFile(
                    'module/module.twig',
                    $this->getSite()->getModulePath($module).'/'.$module . '.module',
                    [
                        'machine_name' => $module,
                        'description' => '',
                    ]
                );
            }
            $module_file_contents = file_get_contents($module_filename);
            if (strpos($module_file_contents, 'function ' . $module . '_theme') !== false) {
                $this->io->warning(
                    [
                    "It looks like you have a hook_theme already declared",
                    "Please manually merge the two hook_theme() implementations in",
                    $module_filename
                    ]
                );
            }

            $this->renderFile(
                'module/src/Entity/entity-content-with-bundle.theme.php.twig',
                $this->getSite()->getModulePath($module).'/'.$module.'.module',
                $parameters,
                FILE_APPEND
            );

            if (strpos($module_file_contents, 'function ' . $module . '_theme_suggestions_' . $entity_name) !== false) {
                $this->io->warning(
                    [
                    "It looks like you have a hook_theme_suggestions_HOOK already declared",
                    "Please manually merge the two hook_theme_suggestions_HOOK() implementations in",
                    $module_filename
                    ]
                );
            }

            $this->renderFile(
                'module/src/Entity/entity-content-with-bundle.theme_hook_suggestions.php.twig',
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
            $this->io->commentBlock(
                [
                    'Add this to your hook_theme:',
                    $content
                ]
            );
        }
    }
}
