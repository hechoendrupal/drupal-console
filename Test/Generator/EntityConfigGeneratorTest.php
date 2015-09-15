<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\EntityConfigGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\EntityConfigGenerator;
use Drupal\AppConsole\Test\DataProvider\EntityConfigDataProviderTrait;

class EntityConfigGeneratorTest extends GeneratorTest
{
    use EntityConfigDataProviderTrait;

    /**
     * EntityConfig generator test
     *
     * @param $module
     * @param $entity_name
     * @param $entity_class
     * @param $label
     *
     * @dataProvider commandData
     */
    public function testGenerateEntityConfig(
        $module,
        $entity_name,
        $entity_class,
        $label
    ) {
        $generator = new EntityConfigGenerator();
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $entity_name,
            $entity_class,
            $label
        );

        $files = [
          $generator->getSite()->getModulePath($module).'/config/schema/'.$entity_name.'.schema.yml',
          $generator->getSite()->getModulePath($module).'/'.$module.'.routing.yml',
          $generator->getSite()->getModulePath($module).'/'.$module.'.links.menu.yml',
          $generator->getSite()->getModulePath($module).'/'.$module.'.links.action.yml',
          $generator->getSite()->getSourcePath($module).'/'.$entity_class.'Interface.php',
          $generator->getSite()->getEntityPath($module).'/'.$entity_class.'.php',
          $generator->getSite()->getFormPath($module).'/'.$entity_class.'Form.php',
          $generator->getSite()->getFormPath($module).'/'.$entity_class.'DeleteForm.php',
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
