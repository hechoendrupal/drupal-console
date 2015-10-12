<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorAuthenticationProviderCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\GeneratorAuthenticationProviderCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\AuthenticationProviderDataProviderTrait;

class GeneratorAuthenticationProviderCommandTest extends GenerateCommandTest
{
    use AuthenticationProviderDataProviderTrait;

    /**
     * AuthenticationProvider generator test
     *
     * @param $module
     * @param $class_name
     *
     * @dataProvider commandData
     */
    public function testGenerateAuthenticationProvider(
        $module,
        $class_name
    ) {
        $command = new GeneratorAuthenticationProviderCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--class-name'     => $class_name
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\AuthenticationProviderGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
