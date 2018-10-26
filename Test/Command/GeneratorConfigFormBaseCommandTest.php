<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorConfigFormBaseCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\ConfigFormBaseCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\ConfigFormBaseDataProviderTrait;

class GeneratorConfigFormBaseCommandTest extends GenerateCommandTest
{
    use ConfigFormBaseDataProviderTrait;

    /**
     * ConfigFormBase generator test
     *
     * @param $module
     * @param $class_name
     * @param $form_id
     * @param $services
     * @param $inputs
     * @param $routing
     *
     * @dataProvider commandData
     */
    public function testGenerateConfigFormBase(
        $module,
        $class_name,
        $form_id,
        $services,
        $inputs,
        $routing
    ) {
        $command = new ConfigFormBaseCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         =>   $module,
              '--class'     =>   $class_name,
              '--form-id'        =>   $form_id,
              '--services'       =>   $services,
              '--inputs'         =>   $inputs
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
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
