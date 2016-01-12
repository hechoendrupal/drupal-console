<?php
/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginRulesActionGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginRulesActionGenerator;
use Drupal\Console\Test\DataProvider\PluginRulesActionDataProviderTrait;

class PluginRulesActionGeneratorTest extends GeneratorTest
{
    use PluginRulesActionDataProviderTrait;

    /**
     * PluginRulesAction generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $category
     * @param $context,
     * @param $type
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginRulesAction(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $category,
        $context,
        $type
    ) {
        $generator = new PluginRulesActionGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $label,
            $plugin_id,
            $category,
            $context,
            $type
        );

        $files = [
          $generator->getSite()->getPluginPath($module, 'Action').'/'.$class_name.'.php',
          $generator->getSite()->getModulePath($module).'/config/install/system.action.'.$plugin_id.'.yml'
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
