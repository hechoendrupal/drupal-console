<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginFieldWidgetGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginFieldWidgetGenerator;
use Drupal\Console\Test\DataProvider\PluginFieldWidgetDataProviderTrait;

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
            file_exists($generator->getSite()->getPluginPath($module, 'Field/FieldWidget') . '/' . $class_name . '.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );
    }
}
