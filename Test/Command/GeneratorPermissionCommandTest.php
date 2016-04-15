<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPermissionCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\PermissionCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\PermissionDataProviderTrait;

class GeneratorPermissionCommandTest extends GenerateCommandTest
{
    use PermissionDataProviderTrait;
    
    /**
     * Permission generator test
     *
     * @param $module
     * @param $permissions
     *
     * @dataProvider commandData
     */
    public function testGeneratePermission(
        $module,
        $permissions
    ) {
        $command = new PermissionCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
            '--module'        => $module,
            '--permissions'   => $permissions,
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\PermissionGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
