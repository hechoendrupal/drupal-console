<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginFieldCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginFieldCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginFieldDataProviderTrait;

class GeneratorPluginFieldCommandTest extends GenerateCommandTest
{
    use PluginFieldDataProviderTrait;
    
    /**
     * Plugin block generator test
     *
     * @param $module
     * @param $type_class_name
     * @param $type_label
     * @param $type_plugin_id
     * @param $type_description
     * @param $formatter_class_name
     * @param $formatter_label
     * @param $formatter_plugin_id
     * @param $widget_class_name
     * @param $widget_label
     * @param $widget_plugin_id
     * @param $field_type
     * @param $default_widget
     * @param $default_formatter
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginField(
        $module,
        $type_class_name,
        $type_label,
        $type_plugin_id,
        $type_description,
        $formatter_class_name,
        $formatter_label,
        $formatter_plugin_id,
        $widget_class_name,
        $widget_label,
        $widget_plugin_id,
        $field_type,
        $default_widget,
        $default_formatter
    ) {
        $command = new PluginFieldCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'                => $module,
              '--type-class'            => $type_class_name,
              '--type-label'            => $type_label,
              '--type-plugin-id'        => $type_plugin_id,
              '--type-description'      => $type_description,
              '--formatter-class'       => $formatter_class_name,
              '--formatter-label'       => $formatter_label,
              '--formatter-plugin-id'   => $formatter_plugin_id,
              '--widget-class'          => $widget_class_name,
              '--widget-label'          => $widget_label,
              '--widget-plugin-id'      => $widget_plugin_id,
              '--field-type'            => $field_type,
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
