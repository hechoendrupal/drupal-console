<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginBlockCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorPluginBlockCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\PluginBlockDataProviderTrait;

class GeneratorPluginBlockCommandTest extends GenerateCommandTest
{
    use PluginBlockDataProviderTrait;
    
    /**
     * Plugin block generator test
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginBlock(
        $module,
        $class_name,
        $label,
        $plugin_id,
        $services,
        $inputs
    ) {
        $command = new GeneratorPluginBlockCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--class-name'     => $class_name,
              '--label'          => $label,
              '--plugin-id'      => $plugin_id,
              '--services'       => $services,
              '--inputs'         => $inputs
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\AppConsole\Generator\PluginBlockGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}