<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginBlockCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\GeneratorPluginBlockCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginBlockDataProviderTrait;

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
        $command = new GeneratorPluginBlockCommand($this->getHelperSet());
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
            ->getMockBuilder('Drupal\Console\Generator\PluginBlockGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
