<?php

/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorJsTestCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\JsTestCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\JsTestDataProviderTrait;

class GeneratorJsTestCommandTest extends GenerateCommandTest
{
    use JsTestDataProviderTrait;

    /**
     * JavaScript test command test
     *
     * @param $module
     * @param $class_name
     *
     * @dataProvider commandData
     */
    public function testCommandJsTest(
        $module,
        $class_name
    ) {
        $command = new JsTestCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
                '--module'            => $module,
                '--class'             => $class_name,
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\JsTestGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
