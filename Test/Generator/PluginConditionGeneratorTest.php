<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\PluginConditionGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PluginConditionGenerator;
use Drupal\Console\Test\DataProvider\PluginConditionDataProviderTrait;

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
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

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
