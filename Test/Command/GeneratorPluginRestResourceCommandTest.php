<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginRestResourceCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginRestResourceCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginRestResourceDataProviderTrait;

class GeneratorPluginRestResourceCommandTest extends GenerateCommandTest
{
    use PluginRestResourceDataProviderTrait;
    
    /**
     * Plugin rest resource generator test
     *
     * @param $module
     * @param $class_name
     * @param $plugin_id
     * @param $plugin_label
     * @param $plugin_url
     * @param $plugin_states
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginRestResource(
        $module,
        $class_name,
        $plugin_id,
        $plugin_label,
        $plugin_url,
        $plugin_states
    ) {
        $command = new PluginRestResourceCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--class'     => $class_name,
              '--plugin-id'      => $plugin_id,
              '--plugin-label'   => $plugin_label,
              '--plugin-url'     => $plugin_url,
              '--plugin-states'  => $plugin_states,
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\PluginRestResourceGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
