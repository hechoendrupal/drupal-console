<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginFieldTypeCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginFieldTypeCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginFieldTypeDataProviderTrait;

class GeneratorPluginFieldTypeCommandTest extends GenerateCommandTest
{
    use PluginFieldTypeDataProviderTrait;
    
    /**
     * Plugin block generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $description
     * @param $default_widget
     * @param $default_formatter
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginFieldType(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $description,
        $default_widget,
        $default_formatter
    ) {
        $command = new PluginFieldTypeCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'                => $module,
              '--class'            => $class_name,
              '--label'                 => $label,
              '--plugin-id'             => $plugin_id,
              '--description'           => $description,
              '--default-widget'        => $default_widget,
              '--default-formatter'     => $default_formatter
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\PluginFieldTypeGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
