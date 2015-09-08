<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginImageEffectCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorPluginImageEffectCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\PluginImageEffectDataProviderTrait;

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
        $command = new GeneratorPluginImageEffectCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--class-name'     => $class_name,
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
            ->getMockBuilder('Drupal\AppConsole\Generator\PluginImageEffectGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
