<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginConditionCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginConditionCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginConditionDataProviderTrait;

class GeneratorPluginConditionCommandTest extends GenerateCommandTest
{
    use PluginConditionDataProviderTrait;
    
    /**
     * Plugin block generator test
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
        $command = new PluginConditionCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'                      => $module,
              '--class'                  => $class_name,
              '--label'                       => $label,
              '--plugin-id'                   => $plugin_id,
              '--context-definition-id'       => $context_definition_id,
              '--context-definition-label'    => $context_definition_label,
              '--context-definition-required' => $context_definition_required
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\PluginConditionGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
