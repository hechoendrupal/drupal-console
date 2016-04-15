<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginImageEffectCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginImageEffectCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginImageEffectDataProviderTrait;

class GeneratorPluginImageEffectCommandTest extends GenerateCommandTest
{
    use PluginImageEffectDataProviderTrait;
    
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
    public function testGeneratePluginImageEffect(
        $module,
        $class_name,
        $plugin_label,
        $plugin_id,
        $description
    ) {
        $command = new PluginImageEffectCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--class'     => $class_name,
              '--label'          => $plugin_label,
              '--plugin-id'      => $plugin_id,
              '--description'    => $description
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\PluginImageEffectGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
