<?php
/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\PluginRestResourceGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\PluginRestResourceGenerator;
use Drupal\AppConsole\Test\DataProvider\PluginRestResourceDataProviderTrait;

class PluginRestResourceGeneratorTest extends GeneratorTest
{
    use PluginRestResourceDataProviderTrait;

    /**
     * PluginRestResource generator test
     *
     * @param $module
     * @param $class_name
     * @param $plugin_label
     * @param $plugin_id
     * @param $plugin_url
     * @param $plugin_states
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginRestResource(
        $module,
        $class_name,
        $plugin_label,
        $plugin_id,
        $plugin_url,
        $plugin_states
    ) {
        $generator = new PluginRestResourceGenerator();
        $generator->setSkeletonDirs(__DIR__ . '/../../templates');
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $plugin_label,
            $plugin_id,
            $plugin_url,
            $plugin_states
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getPluginPath($module, 'rest').'/resource/'.$class_name.'.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );
    }
}
