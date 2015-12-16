<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginBlockCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginBlockCommand;
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
        $command = new PluginBlockCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--class'     => $class_name,
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
