<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginFieldTypeCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorPluginFieldTypeCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\PluginFieldTypeDataProviderTrait;

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
        $command = new GeneratorPluginFieldTypeCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'                => $module,
              '--class-name'            => $class_name,
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
            ->getMockBuilder('Drupal\AppConsole\Generator\PluginFieldTypeGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
