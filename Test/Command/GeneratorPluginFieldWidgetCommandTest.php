<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginFieldWidgetCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorPluginFieldWidgetCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\PluginFieldWidgetDataProviderTrait;

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
        $command = new GeneratorPluginFieldWidgetCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'               => $module,
              '--class-name'           => $class_name,
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
            ->getMockBuilder('Drupal\AppConsole\Generator\PluginFieldWidgetGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
