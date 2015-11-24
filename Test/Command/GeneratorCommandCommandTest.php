<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorCommandCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\CommandCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\CommandDataProviderTrait;

class GeneratorCommandCommandTest extends GenerateCommandTest
{
    use CommandDataProviderTrait;
    
    /**
     * Command generator test
     *
     * @param $module
     * @param $name
     * @param $class
     * @param $containerAware
     *
     * @dataProvider commandData
     */
    public function testGenerateCommand(
        $module,
        $name,
        $class,
        $containerAware
    ) {
        $command = new CommandCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'          => $module,
              '--name'            => $name,
              '--class'           => $class,
              '--container-aware' => $containerAware
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\CommandGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
