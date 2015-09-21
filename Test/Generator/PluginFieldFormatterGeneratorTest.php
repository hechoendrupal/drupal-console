<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\PluginFieldFormatterGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\PluginFieldFormatterGenerator;
use Drupal\AppConsole\Test\DataProvider\PluginFieldFormatterDataProviderTrait;

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
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
        $generator->setHelpers($this->getHelperSet());

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
