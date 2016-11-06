<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GenerateEntityBundleCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;

use Drupal\Console\Test\Builders\a as an;
use Drupal\Console\Command\Generate\EntityBundleCommand;
use Drupal\Console\Test\Command\GenerateCommandTest;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\EntityBundleDataProviderTrait;

class EntityBundleCommandTest extends GenerateCommandTest
{
    use EntityBundleDataProviderTrait;

    /**
     * ContentType generator test
     *
     * @param $module
     * @param $bundleName
     * @param $bundleTitle
     *
     * @dataProvider commandData
     */
    public function testGenerateContentType(
        $module,
        $bundleName,
        $bundleTitle
    ) {
        $manager = an::extensionManager();
        $generator = an::entityBundleGenerator();
        $command = new EntityBundleCommand(
            new Validator($manager),
            $generator->reveal(),
            $manager
        );
        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
                '--module' => $module,
                '--bundle-name' => $bundleName,
                '--bundle-title' => $bundleTitle
            ],
            ['interactive' => false]
        );

        $generator
            ->generate($module, $bundleName, $bundleTitle)
            ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }
}
