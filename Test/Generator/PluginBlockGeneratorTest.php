<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginBlockGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginBlockGenerator;
use Drupal\Console\Test\DataProvider\PluginBlockDataProviderTrait;

class PluginBlockGeneratorTest extends GeneratorTest
{
    use PluginBlockDataProviderTrait;

    /**
     * PluginBlock generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $services
     * @param $inputs
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginBlock(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $services,
        $inputs
    ) {
        $generator = new PluginBlockGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $label,
            $plugin_id,
            $services,
            $inputs
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getPluginPath($module, 'Block').'/'.$class_name.'.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );
    }
}
