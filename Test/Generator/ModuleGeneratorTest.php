<?php
/**
 * @file
 * Contains Drupal\Console\Test\Generator\ModuleGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\ModuleGenerator;
use Drupal\Console\Test\DataProvider\ModuleDataProviderTrait;

/**
 * Class ModuleGeneratorTest
 * @package Drupal\Console\Test\Generator
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
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
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
