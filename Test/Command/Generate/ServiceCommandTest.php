<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorServiceCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;

use Drupal\Console\Command\Generate\ServiceCommand;
use Drupal\Console\Test\Builders\a;
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
        $command = new ServiceCommand(
            a::extensionManager(),
            a::serviceGenerator()->reveal(),
            a::stringConverter()->reveal(),
            a::chainQueue()->reveal()
        );

        $commandTester = new CommandTester($command);

        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );

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
}
