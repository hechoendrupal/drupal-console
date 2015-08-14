<?php
/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\ModuleGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\ModuleGenerator;
use Drupal\AppConsole\Test\DataProvider\ModuleDataProviderTrait;

/**
 * Class ModuleGeneratorTest
 * @package Drupal\AppConsole\Test\Generator
 */
class ModuleGeneratorTest extends GeneratorTest
{
    use ModuleDataProviderTrait;

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
    public function testGenerateModule(
        $module,
        $machine_name,
        $module_path,
        $description,
        $core,
        $package,
        $composer,
        $dependencies
    ) {
        $generator = new ModuleGenerator();
        $generator->setSkeletonDirs(__DIR__ . '/../../templates');
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $machine_name,
            $module_path,
            $description,
            $core,
            $package,
            $composer,
            $dependencies
        );

        $files = [
          $machine_name . '.info.yml',
          $machine_name . '.module',
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($module_path . '/' . $machine_name . '/' . $file),
                sprintf('%s has been generated', $module_path . '/' . $machine_name . '/' . $file)
            );
        }

        if ($composer) {
            $this->assertTrue(
                file_exists($module_path . '/' . $machine_name . '/composer.json'),
                sprintf('%s has been generated', $module_path . '/' . $machine_name . '/composer.json')
            );
        }
    }
}
