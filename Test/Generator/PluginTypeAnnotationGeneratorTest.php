<?php
/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\PluginTypeAnnotationGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\PluginTypeAnnotationGenerator;
use Drupal\AppConsole\Test\DataProvider\PluginTypeAnnotationDataProviderTrait;

class PluginTypeAnnotationGeneratorTest extends GeneratorTest
{
    use PluginTypeAnnotationDataProviderTrait;

    /**
     * PluginTypeAnnotation generator test
     *
     * @param $module
     * @param $class_name
     * @param $machine_name
     * @param $label
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginTypeAnnotation(
        $module,
        $class_name,
        $machine_name,
        $label
    ) {
        $generator = new PluginTypeAnnotationGenerator();
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $machine_name,
            $label
        );

        $files = [
          $generator->getSite()->getSourcePath($module) . '/Annotation/' . $class_name . '.php',
          $generator->getSite()->getSourcePath($module).'/Plugin/' . $class_name . 'Base.php',
          $generator->getSite()->getSourcePath($module).'/Plugin/' . $class_name . 'Interface.php',
          $generator->getSite()->getSourcePath($module).'/Plugin/' . $class_name . 'Manager.php',
          $generator->getSite()->getModulePath($module) . '/' . $module . '.services.yml'
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
