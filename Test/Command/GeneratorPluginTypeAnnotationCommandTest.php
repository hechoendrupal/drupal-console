<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPluginTypeAnnotationCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorPluginTypeAnnotationCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\PluginTypeYamlDataProviderTrait;

class GeneratorPluginTypeAnnotationCommandTest extends GenerateCommandTest
{
    use PluginTypeYamlDataProviderTrait;

    /**
     * Plugin type yaml generator test
     *
     * @param $module
     * @param $class_name
     * @param $machine_name
     * @param $label
     *
     * @dataProvider commandData
     */
    public function testGeneratePluginTypeYaml(
        $module,
        $class_name,
        $machine_name,
        $label
    ) {
        $command = new GeneratorPluginTypeAnnotationCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'            => $module,
              '--class-name'        => $class_name,
              '--machine-name'      => $machine_name,
              '--label'             => $label
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
