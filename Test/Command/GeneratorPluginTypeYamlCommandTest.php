<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginTypeYamlCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorPluginTypeYamlCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\PluginTypeYamlDataProviderTrait;

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
        $command = new GeneratorPluginTypeYamlCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'            => $module,
              '--class-name'        => $plugin_class,
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
            ->getMockBuilder('Drupal\AppConsole\Generator\PluginTypeYamlGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
