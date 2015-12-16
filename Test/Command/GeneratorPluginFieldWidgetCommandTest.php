<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginFieldWidgetCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginFieldWidgetCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginFieldWidgetDataProviderTrait;

class GeneratorPluginFieldWidgetCommandTest extends GenerateCommandTest
{
    use PluginFieldWidgetDataProviderTrait;
    
    /**
     * Plugin block generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $field_type
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginFieldWidget(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $field_type
    ) {
        $command = new PluginFieldWidgetCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'               => $module,
              '--class'           => $class_name,
              '--label'                => $label,
              '--plugin-id'            => $plugin_id,
              '--field-type'           => $field_type
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\PluginFieldWidgetGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
