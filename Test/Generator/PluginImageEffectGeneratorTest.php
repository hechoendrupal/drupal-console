<?php
/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginImageEffectGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginImageEffectGenerator;
use Drupal\Console\Test\DataProvider\PluginImageEffectDataProviderTrait;

class PluginImageEffectGeneratorTest extends GeneratorTest
{
    use PluginImageEffectDataProviderTrait;

    /**
     * PluginImageEffect generator test
     *
     * @param $module
     * @param $class_name
     * @param $plugin_label
     * @param $plugin_id
     * @param $description
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginImageEffect(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $description
    ) {
        $generator = new PluginImageEffectGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $label,
            $plugin_id,
            $description
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getPluginPath($module, 'ImageEffect').'/'.$class_name.'.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );
    }
}
