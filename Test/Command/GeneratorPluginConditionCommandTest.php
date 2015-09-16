<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginConditionCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorPluginConditionCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\PluginConditionDataProviderTrait;

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
        $command = new GeneratorPluginConditionCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'                      => $module,
              '--class-name'                  => $class_name,
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
            ->getMockBuilder('Drupal\AppConsole\Generator\PluginConditionGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
