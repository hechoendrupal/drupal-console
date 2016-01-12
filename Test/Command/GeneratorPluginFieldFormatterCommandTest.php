<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginFieldFormatterCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginFieldFormatterCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginFieldFormatterDataProviderTrait;

class GeneratorPluginFieldFormatterCommandTest extends GenerateCommandTest
{
    use PluginFieldFormatterDataProviderTrait;
    
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
    public function testGeneratePluginFieldFormatter(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $field_type
    ) {
        $command = new PluginFieldFormatterCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'                => $module,
              '--class'            => $class_name,
              '--label'                 => $label,
              '--plugin-id'             => $plugin_id,
              '--field-type'            => $field_type
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\PluginFieldFormatterGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
