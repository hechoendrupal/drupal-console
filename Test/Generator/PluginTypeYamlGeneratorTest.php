<?php
/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginTypeYamlGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginTypeYamlGenerator;
use Drupal\Console\Test\DataProvider\PluginTypeYamlDataProviderTrait;

class PluginTypeYamlGeneratorTest extends GeneratorTest
{
    use PluginTypeYamlDataProviderTrait;

    /**
     * PluginTypeYaml generator test
     *
     * @param $module
     * @param $plugin_class
     * @param $plugin_name
     * @param $plugin_file_name
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginTypeYaml(
        $module,
        $plugin_class,
        $plugin_name,
        $plugin_file_name
    ) {
        $generator = new PluginTypeYamlGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $plugin_class,
            $plugin_name,
            $plugin_file_name
        );

        $files = [
          $generator->getSite()->getSourcePath($module) . '/' . $plugin_class . 'Manager.php',
          $generator->getSite()->getSourcePath($module) . '/' . $plugin_class . 'ManagerInterface.php',
          $generator->getSite()->getModulePath($module) . '/' . $module . '.services.yml',
          $generator->getSite()->getModulePath($module) . '/' . $module . '.' . $plugin_file_name . '.yml'
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
