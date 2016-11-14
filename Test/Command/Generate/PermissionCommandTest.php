<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorPermissionCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;
use Drupal\Console\Test\Builders\a as an;
use Drupal\Console\Command\Generate\PermissionCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Test\DataProvider\PermissionDataProviderTrait;

class PermissionCommandTest extends GenerateCommandTest
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
        $generator = an::permissionGenerator();
        $command = new PermissionCommand(
          an::extensionManager(),
          new StringConverter()
        );
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

}
