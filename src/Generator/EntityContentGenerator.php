<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\EntityContentGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\TwigRenderer;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Site;

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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $module = $parameters['module'];
        $entity_name = $parameters['entity_name'];
        $entity_class = $parameters['entity_class'];
        $bundle_entity_type = $parameters['bundle_entity_type'];
        $is_translatable = $parameters['is_translatable'];
        $revisionable = $parameters['revisionable'];
        $has_forms = $parameters['has_forms'];

        $moduleInstance = $this->extensionManager->getModule($module);
        $moduleDir = $moduleInstance->getPath();
        $modulePath = $moduleDir . '/' . $module;
        $moduleSourcePath = $moduleInstance->getSourcePath() . '/' . $entity_class;
        $moduleFormPath = $moduleInstance->getFormPath() . '/' . $entity_class;
        $moduleEntityPath = $moduleInstance->getEntityPath() . '/' . $entity_class;
        $moduleTemplatePath = $moduleInstance->getTemplatePath() . '/';
        $moduleFileName = $modulePath . '.module';

        $this->renderFile(
            'module/permissions-entity-content.yml.twig',
            $modulePath . '.permissions.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/accesscontrolhandler-entity-content.php.twig',
            $moduleSourcePath . 'AccessControlHandler.php',
            $parameters
        );

        if ($is_translatable) {
            $this->renderFile(
                'module/src/entity-translation-handler.php.twig',
                $moduleSourcePath . 'TranslationHandler.php',
                $parameters
            );
        }

        $this->renderFile(
            'module/src/Entity/interface-entity-content.php.twig',
            $moduleEntityPath . 'Interface.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/entity-content.php.twig',
            $moduleEntityPath . '.php',
            $parameters
        );

        $this->renderFile(
            'module/src/Entity/entity-content-views-data.php.twig',
            $moduleEntityPath . 'ViewsData.php',
            $parameters
        );

        $this->renderFile(
            'module/src/listbuilder-entity-content.php.twig',
            $moduleSourcePath . 'ListBuilder.php',
            $parameters
        );

        if($has_forms) {
            $this->renderFile(
                'module/src/entity-content-route-provider.php.twig',
                $moduleSourcePath . 'HtmlRouteProvider.php',
                $parameters
            );

            $this->renderFile(
                'module/links.menu-entity-content.yml.twig',
                $modulePath . '.links.menu.yml',
                $parameters,
                FILE_APPEND
            );

            $this->renderFile(
                'module/links.task-entity-content.yml.twig',
                $modulePath . '.links.task.yml',
                $parameters,
                FILE_APPEND
            );

            $this->renderFile(
                'module/links.action-entity-content.yml.twig',
                $modulePath . '.links.action.yml',
                $parameters,
                FILE_APPEND
            );

            $this->renderFile(
                'module/src/Entity/Form/entity-settings.php.twig',
                $moduleFormPath . 'SettingsForm.php',
                $parameters
            );

            $this->renderFile(
                'module/src/Entity/Form/entity-content.php.twig',
                $moduleFormPath . 'Form.php',
                $parameters
            );


            $this->renderFile(
                'module/src/Entity/Form/entity-content-delete.php.twig',
                $moduleFormPath . 'DeleteForm.php',
                $parameters
            );

            $this->renderFile(
                'module/templates/entity-html.twig',
                $moduleTemplatePath . $entity_name . '.html.twig',
                $parameters
            );

            $this->renderFile(
                'module/entity-content-page.php.twig',
                $moduleDir . '/' . $entity_name . '.page.inc',
                $parameters
            );
        }


        if ($revisionable) {
            if ($has_forms) {
                if ($is_translatable) {
                    $this->renderFile(
                        'module/src/Entity/Form/entity-content-revision-revert-translation.php.twig',
                        $moduleFormPath . 'RevisionRevertTranslationForm.php',
                        $parameters
                    );
                }

                $this->renderFile(
                    'module/src/Entity/Form/entity-content-revision-delete.php.twig',
                    $moduleFormPath . 'RevisionDeleteForm.php',
                    $parameters
                );

                $this->renderFile(
                    'module/src/Entity/Form/entity-content-revision-revert.php.twig',
                    $moduleFormPath . 'RevisionRevertForm.php',
                    $parameters
                );

                $this->renderFile(
                    'module/src/Controller/entity-controller.php.twig',
                    $moduleInstance->getControllerPath()  . '/' . $entity_class . 'Controller.php',
                    $parameters
                );
            }

            $this->renderFile(
                'module/src/entity-storage.php.twig',
                $moduleSourcePath . 'Storage.php',
                $parameters
            );
            $this->renderFile(
                'module/src/interface-entity-storage.php.twig',
                $moduleSourcePath . 'StorageInterface.php',
                $parameters
            );
        }

        if ($bundle_entity_type) {
            $this->renderFile(
                'module/templates/entity-with-bundle-content-add-list-html.twig',
                $moduleTemplatePath . '/' . str_replace('_', '-', $entity_name) . '-content-add-list.html.twig',
                $parameters
            );

            // Check for hook_theme() in module file and warn ...
            // Check if the module file exists.
            if (!file_exists($moduleFileName)) {
                $this->renderFile(
                    'module/module.twig',
                    $moduleFileName,
                    [
                        'machine_name' => $module,
                        'description' => '',
                    ]
                );
            }
            $module_file_contents = file_get_contents($moduleFileName);
            if (strpos($module_file_contents, 'function ' . $module . '_theme') !== false) {
                $this->io->warning(
                    [
                    'It looks like you have a hook_theme already declared',
                    'Please manually merge the two hook_theme() implementations in',
                        $moduleFileName
                    ]
                );
            }

            $this->renderFile(
                'module/src/Entity/entity-content-with-bundle.theme.php.twig',
                $moduleFileName,
                $parameters,
                FILE_APPEND
            );

            if (strpos($module_file_contents, 'function ' . $module . '_theme_suggestions_' . $entity_name) !== false) {
                $this->io->warning(
                    [
                        'It looks like you have a hook_theme_suggestions_HOOK already declared',
                        'Please manually merge the two hook_theme_suggestions_HOOK() implementations in',
                        $moduleFileName
                    ]
                );
            }

            $this->renderFile(
                'module/src/Entity/entity-content-with-bundle.theme_hook_suggestions.php.twig',
                $moduleFileName,
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
