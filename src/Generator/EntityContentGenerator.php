<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\EntityContentGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Site;
use Drupal\Console\Core\Utils\TwigRenderer;

class EntityContentGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Site
     */
    protected $site;

    /**
 * @var TwigRenderer
*/
    protected $twigrenderer;

    protected $io;

    /**
     * EntityContentGenerator constructor.
     *
     * @param Manager      $extensionManager
     * @param Site         $site
     * @param TwigRenderer $twigrenderer
     */
    public function __construct(
        Manager $extensionManager,
        Site $site,
        TwigRenderer $twigrenderer
    ) {
        $this->extensionManager = $extensionManager;
        $this->site = $site;
        $this->twigrenderer = $twigrenderer;
    }

    public function setIo($io)
    {
        $this->io = $io;
    }


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
     * @param bool   $revisionable       Revision configuration
     */
    public function generate($module, $entity_name, $entity_class, $label, $base_path, $is_translatable, $bundle_entity_type = null, $revisionable = false)
    {
        $parameters = [
            'module' => $module,
            'entity_name' => $entity_name,
            'entity_class' => $entity_class,
            'label' => $label,
            'bundle_entity_type' => $bundle_entity_type,
            'base_path' => $base_path,
            'is_translatable' => $is_translatable,
            'revisionable' => $revisionable,
        ];

        $this->renderFile(
            'module/permissions-entity-content.yml.twig',
            $this->extensionManager->getModule($module)->getPath().'/'.$module.'.permissions.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/links.menu-entity-content.yml.twig',
            $this->extensionManager->getModule($module)->getPath().'/'.$module.'.links.menu.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/links.task-entity-content.yml.twig',
            $this->extensionManager->getModule($module)->getPath().'/'.$module.'.links.task.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/links.action-entity-content.yml.twig',
            $this->extensionManager->getModule($module)->getPath().'/'.$module.'.links.action.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/accesscontrolhandler-entity-content.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath().'/'.$entity_class.'AccessControlHandler.php',
            $parameters
        );

        if ($is_translatable) {
            $this->renderFile(
                'module/src/entity-translation-handler.php.twig',
                $this->extensionManager->getModule($module)->getSourcePath().'/'.$entity_class.'TranslationHandler.php',
                $parameters
            );
        }

        $this->renderFile(
            'module/src/Entity/interface-entity-content.php.twig',
            $this->extensionManager->getModule($module)->getEntityPath().'/'.$entity_class.'Interface.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/entity-content.php.twig',
            $this->extensionManager->getModule($module)->getEntityPath().'/'.$entity_class.'.php',
            $parameters
        );

        $this->renderFile(
            'module/src/entity-content-route-provider.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath().'/'.$entity_class.'HtmlRouteProvider.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/entity-content-views-data.php.twig',
            $this->extensionManager->getModule($module)->getEntityPath().'/'.$entity_class.'ViewsData.php',
            $parameters
        );

        $this->renderFile(
            'module/src/listbuilder-entity-content.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath().'/'.$entity_class.'ListBuilder.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/Form/entity-settings.php.twig',
            $this->extensionManager->getModule($module)->getFormPath().'/'.$entity_class.'SettingsForm.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/Form/entity-content.php.twig',
            $this->extensionManager->getModule($module)->getFormPath().'/'.$entity_class.'Form.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/Form/entity-content-delete.php.twig',
            $this->extensionManager->getModule($module)->getFormPath().'/'.$entity_class.'DeleteForm.php',
            $parameters
        );

        $this->renderFile(
            'module/entity-content-page.php.twig',
            $this->extensionManager->getModule($module)->getPath().'/'.$entity_name.'.page.inc',
            $parameters
        );

        $this->renderFile(
            'module/templates/entity-html.twig',
            $this->extensionManager->getModule($module)->getTemplatePath().'/'.$entity_name.'.html.twig',
            $parameters
        );

        if ($revisionable) {
            $this->renderFile(
                'module/src/Entity/Form/entity-content-revision-delete.php.twig',
                $this->extensionManager->getModule($module)->getFormPath() .'/'.$entity_class.'RevisionDeleteForm.php',
                $parameters
            );
            $this->renderFile(
                'module/src/Entity/Form/entity-content-revision-revert-translation.php.twig',
                $this->extensionManager->getModule($module)->getFormPath() .'/'.$entity_class.'RevisionRevertTranslationForm.php',
                $parameters
            );
            $this->renderFile(
                'module/src/Entity/Form/entity-content-revision-revert.php.twig',
                $this->extensionManager->getModule($module)->getFormPath().'/'.$entity_class.'RevisionRevertForm.php',
                $parameters
            );
            $this->renderFile(
                'module/src/entity-storage.php.twig',
                $this->extensionManager->getModule($module)->getSourcePath() .'/'.$entity_class.'Storage.php',
                $parameters
            );
            $this->renderFile(
                'module/src/interface-entity-storage.php.twig',
                $this->extensionManager->getModule($module)->getSourcePath() .'/'.$entity_class.'StorageInterface.php',
                $parameters
            );
            $this->renderFile(
                'module/src/Controller/entity-controller.php.twig',
                $this->extensionManager->getModule($module)->getControllerPath() .'/'.$entity_class.'Controller.php',
                $parameters
            );
        }

        if ($bundle_entity_type) {
            $this->renderFile(
                'module/templates/entity-with-bundle-content-add-list-html.twig',
                $this->extensionManager->getModule($module)->getTemplatePath().'/'.str_replace('_', '-', $entity_name).'-content-add-list.html.twig',
                $parameters
            );

            // Check for hook_theme() in module file and warn ...
            $module_filename = $this->extensionManager->getModule($module)->getPath().'/'.$module.'.module';
            // Check if the module file exists.
            if (!file_exists($module_filename)) {
                $this->renderFile(
                    'module/module.twig',
                    $this->extensionManager->getModule($module)->getPath().'/'.$module . '.module',
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
                $this->extensionManager->getModule($module)->getPath().'/'.$module.'.module',
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
                $this->extensionManager->getModule($module)->getPath().'/'.$module.'.module',
                $parameters,
                FILE_APPEND
            );
        }

        $content = $this->twigrenderer->render(
            'module/src/Entity/entity-content.theme.php.twig',
            $parameters
        );


        //@TODO:
        /**
        if ($this->isLearning()) {
            $this->io->commentBlock(
                [
                    'Add this to your hook_theme:',
                    $content
                ]
            );
        }
        */
    }
}
