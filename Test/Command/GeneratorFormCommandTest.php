<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorFormCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\FormCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\FormDataProviderTrait;

class GeneratorFormCommandTest extends GenerateCommandTest
{
    use FormDataProviderTrait;

    /**
     * Form generator test
     *
     * @param $module
     * @param $class_name
     * @param $services
     * @param $inputs
     * @param $form_id
     * @param $routing_update
     *
     * @dataProvider commandData
     */
    public function testGenerateForm(
        $module,
        $class_name,
        $form_id,
        $services,
        $inputs,
        $routing_update
    ) {
        $command = $this->getGeneratorConfig();
        $command->setHelperSet($this->getHelperSet(null));
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
            '--module'              => $module,
            '--class'          => $class_name,
            '--services'            => $services,
            '--inputs'              => $inputs,
            '--form-id'             => $form_id
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGeneratorConfig()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Command\Generate\ConfigFormBaseCommand')
            ->setMethods(['getModules', 'getServices', '__construct'])
            ->setConstructorArgs([$this->getHelperSet()])
            ->getMock();
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\FormGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
