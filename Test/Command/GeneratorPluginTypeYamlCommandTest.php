<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginTypeYamlCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginTypeYamlCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginTypeYamlDataProviderTrait;

class GeneratorPluginTypeYamlCommandTest extends GenerateCommandTest
{
    use PluginTypeYamlDataProviderTrait;

    /**
     * Plugin type yaml generator test
     *
     * @param $module
     * @param $plugin_class
     * @param $plugin_name
     * @param $plugin_file_name
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginTypeYaml(
        $module,
        $plugin_class,
        $plugin_name,
        $plugin_file_name
    ) {
        $command = new PluginTypeYamlCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'            => $module,
              '--class'        => $plugin_class,
              '--plugin-name'       => $plugin_name,
              '--plugin-file-name'  => $plugin_file_name
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\PluginTypeYamlGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
