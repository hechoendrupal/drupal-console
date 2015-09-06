<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Test\Command\GeneratorPermissionCommandTest.
 */

namespace Drupal\AppConsole\Test\Command;

use Drupal\AppConsole\Command\GeneratorPermissionCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\AppConsole\Test\DataProvider\PermissionDataProviderTrait;

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

        $command = new GeneratorPermissionCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
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
            ->getMockBuilder('Drupal\AppConsole\Generator\PermissionGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
