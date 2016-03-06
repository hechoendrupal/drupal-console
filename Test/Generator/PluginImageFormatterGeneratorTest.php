<?php
/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginImageFormatterGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginImageFormatterGenerator;
use Drupal\Console\Test\DataProvider\PluginImageFormatterDataProviderTrait;

class PluginImageFormatterGeneratorTest extends GeneratorTest
{
    use PluginImageFormatterDataProviderTrait;

    /**
     * PluginImageFormatter generator test
     *
     * @param $module
     * @param $class_name
     * @param $plugin_label
     * @param $plugin_id
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginImageFormatter(
        $module,
        $class_name,
        $label,
        $plugin_id
    ) {
        $generator = new PluginImageFormatterGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $label,
            $plugin_id
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getPluginPath($module, 'Field/FieldFormatter') . '/' . $class_name . '.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );
    }
}
