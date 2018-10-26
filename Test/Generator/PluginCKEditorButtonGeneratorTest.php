<?php
/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginCKEditorButtonGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginCKEditorButtonGenerator;
use Drupal\Console\Test\DataProvider\PluginCKEditorButtonDataProviderTrait;

class PluginCKEditorButtonGeneratorTest extends GeneratorTest
{
    use PluginCKEditorButtonDataProviderTrait;

    /**
     * PluginCKEditorButton generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $button_name
     * @param $button_icon_path
     *
     * @dataProvider commandData
     */
    public function testGenerateCKEditorButtonEffect(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $button_name,
        $button_icon_path
    ) {
        $generator = new PluginCKEditorButtonGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $label,
            $plugin_id,
            $button_name,
            $button_icon_path
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getPluginPath($module, 'CKEditorPlugin').'/'.$class_name.'.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );
    }
}
