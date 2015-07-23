<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\ControllerGeneratorTest.
 *
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\ControllerGenerator;
use Drupal\AppConsole\Test\DataProvider\ControllerDataProviderTrait;

class ControllerGeneratorTest extends GeneratorTest
{

    use ControllerDataProviderTrait;

    /**
     * Module generator test
     *
     * @param $module
     * @param $machine_name
     * @param $module_path,
     * @param $description
     * @param $core
     * @param $package
     * @param $composer
     * @param $dependencies
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
    )
    {
        $generator = new ControllerGenerator();
        $generator->setSkeletonDirs(__DIR__ . '/../../templates');

        $generator->generate(
          $module,
          $class_name,
          $routes,
          $test,
          $build_services,
          $class_machine_name
        );

        $files = [
          $module . '/' . $class_name . '.php',
          $module . '/' . $module . '.routing.yml'
        ];

        foreach ($files as $file) {
            $this->assertTrue(
              file_exists($file),
              sprintf('%s has been generated', $file)
            );
        }

        if ($test) {
            $this->assertTrue(
              file_exists( $this->getTestPath($module, 'Controller') . '/' . $class_name.'Test.php'),
              sprintf('Generate test class %s has been generated ', $class_name.'Test.php')
            );
        }
    }
}
