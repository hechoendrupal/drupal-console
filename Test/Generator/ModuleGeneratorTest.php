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
     * @param $moduleFile
     * @param $featureBundle
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
        $moduleFile,
        $featureBundle,
        $composer,
        $dependencies
    ) {
        $generator = new ModuleGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $machine_name,
            $module_path,
            $description,
            $core,
            $package,
            $moduleFile,
            $featureBundle,
            $composer,
            $dependencies
        );

        $this->assertTrue(
            file_exists($module_path . '/' . $machine_name . '/' . $machine_name . '.info.yml'),
            sprintf('%s has been generated', $module_path . '/' . $machine_name . '.info.yml')
        );

        if ($moduleFile) {
            $this->assertTrue(
                file_exists($module_path . '/' . $machine_name . '/' . $machine_name . '.module'),
                sprintf('%s has been generated', $module_path . '/' . $machine_name . '/' . $machine_name . '.module')
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
