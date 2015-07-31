<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginTypeYamlCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

class GeneratorPluginTypeYamlCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getDataInteractive
     */
    public function testInteractiveCommand($options, $expected, $input)
    {
        list($module, $plugin_class, $plugin_name, $plugin_file_name) = $expected;

        $generator = $this->getGenerator();

        $generator
          ->expects($this->once())
          ->method('generate')
          ->with($module, $plugin_class, $plugin_name, $plugin_file_name);

        $command = $this->getCommand($generator, $input);
        $cmd = new CommandTester($command);
        $cmd->execute($options);
    }

    public function getDataInteractive()
    {
        return [
          [
            [],
            ['Foo', 'MyPlugin', 'my_plugin', 'my.plugin'],
            "Foo\nMyPlugin\nmy_plugin\nmy.plugin\nyes\n"
          ],
        ];
    }

    public function getCommand($generator, $input)
    {
        $command = $this->getMockBuilder('Drupal\AppConsole\Command\GeneratorPluginTypeYamlCommand')
          ->setMethods(['getModules', 'getServices', '__construct'])
          ->setConstructorArgs([$this->getTranslationHelper()])
          ->getMock();

        $command->expects($this->any())
          ->method('getModules')
          ->will($this->returnValue(['Foo']));

        $command->setGenerator($generator);
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));

        return $command;
    }

    private function getGenerator()
    {
        return $this
          ->getMockBuilder('Drupal\AppConsole\Generator\PluginTypeYamlGenerator')
          ->disableOriginalConstructor()
          ->setMethods(['generate'])
          ->getMock();
    }
}
