<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\EntityContentGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\EntityContentGenerator;
use Drupal\Console\Test\DataProvider\EntityContentDataProviderTrait;

class EntityContentGeneratorTest extends GeneratorTest
{
    use EntityContentDataProviderTrait;

    /**
     * EntityContent generator test
     *
     * @param $module
     * @param $entity_name
     * @param $entity_class
     * @param $label
     * @param $base_path
     * @param $is_translatable
     * @param $revisionable
     *
     * @dataProvider commandData
     */
    public function testGenerateEntityContent(
        $module,
        $entity_name,
        $entity_class,
        $label,
        $base_path,
        $is_translatable,
        $revisionable
    ) {
        $generator = new EntityContentGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $entity_name,
            $entity_class,
            $label,
            $base_path,
            $is_translatable,
            null,
            $revisionable
        );

        $files = [
          $generator->getSite()->getModulePath($module).'/'.$module.'.permissions.yml',
          $generator->getSite()->getModulePath($module).'/'.$module.'.links.menu.yml',
          $generator->getSite()->getModulePath($module).'/'.$module.'.links.task.yml',
          $generator->getSite()->getModulePath($module).'/'.$module.'.links.action.yml',
          $generator->getSite()->getEntityPath($module).'/'.$entity_class.'Interface.php',
          $generator->getSite()->getEntityPath($module).'/'.$entity_class.'.php',
          $generator->getSite()->getEntityPath($module).'/'.$entity_class.'ViewsData.php',
          $generator->getSite()->getSourcePath($module).'/'.$entity_class.'AccessControlHandler.php',
          $generator->getSite()->getSourcePath($module).'/'.$entity_class.'HtmlRouteProvider.php',
          $generator->getSite()->getSourcePath($module).'/'.$entity_class.'ListBuilder.php',
          $generator->getSite()->getSourcePath($module).'/'.$entity_class.'Storage.php',
          $generator->getSite()->getSourcePath($module).'/'.$entity_class.'StorageInterface.php',
          $generator->getSite()->getFormPath($module).'/'.$entity_class.'SettingsForm.php',
          $generator->getSite()->getFormPath($module).'/'.$entity_class.'Form.php',
          $generator->getSite()->getFormPath($module).'/'.$entity_class.'DeleteForm.php',
          $generator->getSite()->getFormPath($module).'/'.$entity_class.'RevisionDeleteForm.php',
          $generator->getSite()->getFormPath($module).'/'.$entity_class.'RevisionRevertTranslationForm.php',
          $generator->getSite()->getFormPath($module).'/'.$entity_class.'RevisionRevertForm.php',
          $generator->getSite()->getControllerPath($module).'/'.$entity_class.'Controller.php',
          $generator->getSite()->getModulePath($module).'/'.$entity_name.'.page.inc',
          $generator->getSite()->getTemplatePath($module).'/'.$entity_name.'.html.twig',
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
