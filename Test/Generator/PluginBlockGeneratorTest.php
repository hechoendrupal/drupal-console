<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\PluginBlockGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\PluginBlockGenerator;
use Drupal\AppConsole\Test\DataProvider\PluginBlockDataProviderTrait;

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
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->translator);
        $generator->setHelpers($this->getHelperSet());

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
