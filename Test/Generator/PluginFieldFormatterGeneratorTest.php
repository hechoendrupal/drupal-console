<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginFieldFormatterGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginFieldFormatterGenerator;
use Drupal\Console\Test\DataProvider\PluginFieldFormatterDataProviderTrait;

class PluginFieldFormatterGeneratorTest extends GeneratorTest
{
    use PluginFieldFormatterDataProviderTrait;

    /**
     * PluginFieldFormatter generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $field_type
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginFieldFormatter(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $field_type
    ) {
        $generator = new PluginFieldFormatterGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $label,
            $plugin_id,
            $field_type
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getPluginPath($module, 'Field/FieldFormatter') . '/' . $class_name . '.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );
    }
}
