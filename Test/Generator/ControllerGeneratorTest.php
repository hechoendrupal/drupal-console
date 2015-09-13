<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\ControllerGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\ControllerGenerator;
use Drupal\AppConsole\Test\DataProvider\ControllerDataProviderTrait;

class ControllerGeneratorTest extends GeneratorTest
{
    use ControllerDataProviderTrait;

    /**
     * Controller generator test
     *
     * @param $module
     * @param $class_name
     * @param $routes
     * @param $test
     * @param $build_services
     * @param $class_machine_name
     *
     * @dataProvider commandData
     */
    public function testGenerateController(
        $module,
        $class_name,
        $routes,
        $test,
        $build_services,
        $class_machine_name
    ) {
        $generator = new ControllerGenerator();
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $routes,
            $test,
            $build_services,
            $class_machine_name
        );

        $files = [
          $generator->getSite()->getControllerPath($module).'/'.$class_name.'.php',
          $generator->getSite()->getModulePath($module).'/'.$module.'.routing.yml'
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }

        if ($test) {
            $this->assertTrue(
                file_exists($generator->getSite()->getTestPath($module, 'Controller') . '/' . $class_name.'Test.php'),
                sprintf('%s does not exist', $class_name.'Test.php')
            );
        }
    }
}
