<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorServiceCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;

use Drupal\Console\Command\Generate\ServiceCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\ServiceDataProviderTrait;
use Drupal\Console\Test\Command\GenerateCommandTest;

class ServiceCommandTest extends GenerateCommandTest
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
     * @param $path_service
     *
     * @dataProvider commandData
     */
    public function testGenerateService(
        $module,
        $name,
        $class,
        $interface,
        $services,
        $path_service
    ) {
        $command = new ServiceCommand($this->getHelperSet());
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
              '--path_service'   => $path_service,
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\ServiceGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
