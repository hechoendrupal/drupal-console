<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginRestResourceCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorPluginRestResourceCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\PluginRestResourceDataProviderTrait;

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
        $command = new GeneratorPluginRestResourceCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--class-name'     => $class_name,
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
            ->getMockBuilder('Drupal\AppConsole\Generator\PluginRestResourceGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}