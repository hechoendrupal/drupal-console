<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\PluginFieldWidgetGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\PluginFieldWidgetGenerator;
use Drupal\AppConsole\Test\DataProvider\PluginFieldWidgetDataProviderTrait;

class PluginFieldWidgetGeneratorTest extends GeneratorTest
{
    use PluginFieldWidgetDataProviderTrait;

    /**
     * PluginFieldWidget generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $field_type
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginFieldWidget(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $field_type
    ) {
        $generator = new PluginFieldWidgetGenerator();
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
            file_exists($generator->getSite()->getPluginPath($module, 'Field/FieldWidget') . '/' . $class_name . '.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );
    }
}
