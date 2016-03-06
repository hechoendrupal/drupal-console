<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\ThemeGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\ThemeGenerator;
use Drupal\Console\Test\DataProvider\ThemeDataProviderTrait;

class ThemeGeneratorTest extends GeneratorTest
{
    use ThemeDataProviderTrait;

    /**
     * Theme generator test
     *
     * @param $theme
     * @param $machine_name
     * @param $theme_path
     * @param $description
     * @param $core
     * @param $package
     * @param $global_library
     * @param $base_theme
     * @param $regions
     * @param $breakpoints
     *
     * @dataProvider commandData
     */
    public function testGenerateTheme(
        $theme,
        $machine_name,
        $theme_path,
        $description,
        $core,
        $package,
        $global_library,
        $base_theme,
        $regions,
        $breakpoints
    ) {
        $generator = new ThemeGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $theme,
            $machine_name,
            $theme_path,
            $description,
            $core,
            $package,
            $global_library,
            $base_theme,
            $regions,
            $breakpoints
        );

        $files = [
          $theme_path . '/' . $machine_name . '/' . $machine_name . '.info.yml',
          $theme_path . '/' . $machine_name .  '/' . $machine_name . '.theme'
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }

        if ($breakpoints) {
            $this->assertTrue(
                file_exists($theme_path . '/' . $machine_name . '.breakpoints.yml'),
                sprintf('%s does not exist', $machine_name . '.breakpoints.yml')
            );
        }
    }
}
