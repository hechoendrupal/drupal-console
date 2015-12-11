<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPluginTypeAnnotationCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PluginTypeAnnotationCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PluginTypeYamlDataProviderTrait;

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
        $command = new PluginTypeAnnotationCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'            => $module,
              '--class'        => $class_name,
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
            ->getMockBuilder('Drupal\Console\Generator\PluginTypeYamlGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
