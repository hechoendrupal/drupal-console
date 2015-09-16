<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\PluginConditionGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\PluginConditionGenerator;
use Drupal\AppConsole\Test\DataProvider\PluginConditionDataProviderTrait;

class PluginConditionGeneratorTest extends GeneratorTest
{
    use PluginConditionDataProviderTrait;

    /**
     * PluginCondition generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $context_definition_id
     * @param $context_definition_label
     * @param $context_definition_required
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginCondition(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $context_definition_id,
        $context_definition_label,
        $context_definition_required
    ) {
        $generator = new PluginConditionGenerator();
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name,
            $label,
            $plugin_id,
            $context_definition_id,
            $context_definition_label,
            $context_definition_required
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getPluginPath($module, 'Condition') . '/' . $class_name . '.php'),
            sprintf('%s does not exist', $class_name.'.php')
        );
    }
}
