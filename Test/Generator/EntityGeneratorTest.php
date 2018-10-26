<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\EntityGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\EntityConfigGenerator;
use Drupal\Console\Test\DataProvider\EntityDataProviderTrait;

class EntityGeneratorTest extends GeneratorTest
{
    use EntityDataProviderTrait;

    /**
     * EntityConfig generator test
     *
     * @param $module
     * @param $entity_name
     * @param $entity_class
     * @param $label
     * @param $base_path
     *
     * @dataProvider commandData
     */
    public function testGenerateEntity(
        $module,
        $entity_name,
        $entity_class,
        $label,
        $base_path
    ) {
        $generator = new EntityConfigGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $entity_name,
            $entity_class,
            $label,
            $base_path
        );

        $files = [
          $generator->getSite()->getModulePath($module).'/config/schema/'.$entity_name.'.schema.yml',
          $generator->getSite()->getModulePath($module).'/'.$module.'.links.menu.yml',
          $generator->getSite()->getModulePath($module).'/'.$module.'.links.action.yml',
          $generator->getSite()->getEntityPath($module).'/'.$entity_class.'Interface.php',
          $generator->getSite()->getEntityPath($module).'/'.$entity_class.'.php',
          $generator->getSite()->getFormPath($module).'/'.$entity_class.'Form.php',
          $generator->getSite()->getFormPath($module).'/'.$entity_class.'DeleteForm.php',
          $generator->getSite()->getSourcePath($module).'/'.$entity_class.'HtmlRouteProvider.php',
          $generator->getSite()->getSourcePath($module).'/'.$entity_class.'ListBuilder.php'
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
