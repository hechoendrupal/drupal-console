<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginFieldTypeGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginFieldTypeGenerator;
use Drupal\Console\Test\DataProvider\PluginFieldTypeDataProviderTrait;

class PluginFieldTypeGeneratorTest extends GeneratorTest
{
    use PluginFieldTypeDataProviderTrait;

    /**
     * PluginFieldType generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $description
     * @param $default_widget
     * @param $default_formatter
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginFieldType(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $description,
        $default_widget,
        $default_formatter
    ) {
        $generator = new PluginFieldTypeGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $label,
            $plugin_id,
            $description,
            $default_widget,
            $default_formatter
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getPluginPath($module, 'Field/FieldType') . '/' . $class_name . '.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );
    }
}
