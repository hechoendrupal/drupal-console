<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorServiceCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorServiceCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\ServiceDataProviderTrait;

class GeneratorServiceCommandTest extends GenerateCommandTest
{
    use ServiceDataProviderTrait;

    /**
     * Service generator test
     *
     * @param $module
     * @param $name
     * @param $class
     * @param $interface
     * @param $services
     *
     * @dataProvider commandData
     */
    public function testGenerateService(
        $module,
        $name,
        $class,
        $interface,
        $services
    ) {
        $command = new GeneratorServiceCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--name'             => $name,
              '--class'     => $class,
              '--interface'      => $interface,
              '--services'       => $services,
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\AppConsole\Generator\ServiceGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
