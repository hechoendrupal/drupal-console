<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorControllerCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\GeneratorControllerCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\ControllerDataProviderTrait;

class GeneratorControllerCommandTest extends GenerateCommandTest
{
    use ControllerDataProviderTrait;

    /**
     * Controller generator test
     *
     * @param $module
     * @param $class_name
     * @param $routes
     * @param $test
     * @param $services
     * @param $class_machine_name
     *
     * @dataProvider commandData
     */
    public function testGenerateController(
        $module,
        $class_name,
        $routes,
        $test,
        $services
    ) {
        $command = new GeneratorControllerCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
            '--module'            => $module,
            '--class-name'        => $class_name,
            '--controller-title'  => $routes[0]['title'],
            '--method-name'       => $routes[0]['method'],
            '--route'             => $routes[0]['route'],
            '--test'              => $test,
            '--services'          => $services,
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\ControllerGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
