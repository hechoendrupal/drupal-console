<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginImageFormatterCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginImageFormatterCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginImageFormatterDataProviderTrait;

class GeneratorPluginImageFormatterCommandTest extends GenerateCommandTest
{
    use PluginImageFormatterDataProviderTrait;
    
    /**
     * Plugin image effect generator test
     *
     * @param $module
     * @param $class_name
     * @param $plugin_label
     * @param $plugin_id
     * @param $description
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginImageFormatter(
        $module,
        $class_name,
        $plugin_label,
        $plugin_id
    ) {
        $command = new PluginImageFormatterCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--class'     => $class_name,
              '--label'          => $plugin_label,
              '--plugin-id'      => $plugin_id
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\PluginImageFormatterGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
