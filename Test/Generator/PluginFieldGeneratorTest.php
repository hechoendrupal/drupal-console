<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginFieldGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginFieldTypeGenerator;
use Drupal\Console\Test\DataProvider\PluginFieldDataProviderTrait;

class PluginFieldGeneratorTest extends GeneratorTest
{
    use PluginFieldDataProviderTrait;

    /**
     * PluginField generator test
     *
     * @param $module
     * @param $type_class_name
     * @param $type_label
     * @param $type_plugin_id
     * @param $type_description
     * @param $formatter_class_name
     * @param $formatter_label
     * @param $formatter_plugin_id
     * @param $widget_class_name
     * @param $widget_label
     * @param $widget_plugin_id
     * @param $field_type
     * @param $default_widget
     * @param $default_formatter
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginFieldType(
        $module,
        $type_class_name,
        $type_label,
        $type_plugin_id,
        $type_description,
        $formatter_class_name,
        $formatter_label,
        $formatter_plugin_id,
        $widget_class_name,
        $widget_label,
        $widget_plugin_id,
        $field_type,
        $default_widget,
        $default_formatter
    ) {
        $generator = new PluginFieldTypeGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $type_class_name,
            $type_label,
            $type_plugin_id,
            $type_description,
            $formatter_class_name,
            $formatter_label,
            $formatter_plugin_id,
            $widget_class_name,
            $widget_label,
            $widget_plugin_id,
            $field_type,
            $default_widget,
            $default_formatter
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getPluginPath($module, 'Field/FieldType') . '/' . $type_class_name . '.php'),
            sprintf('%s does not exist', $type_class_name.'.php')
        );
    }
}
