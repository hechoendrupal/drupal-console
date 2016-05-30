<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\ControllerGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\ControllerGenerator;
use Drupal\Console\Test\DataProvider\ControllerDataProviderTrait;

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
        $build_services
    ) {
        $generator = new ControllerGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $routes,
            $test,
            $build_services
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
