<?php
/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\PluginImageFormatterGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\PluginImageFormatterGenerator;
use Drupal\AppConsole\Test\DataProvider\PluginImageFormatterDataProviderTrait;

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
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
        $generator->setHelpers($this->getHelperSet());

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
