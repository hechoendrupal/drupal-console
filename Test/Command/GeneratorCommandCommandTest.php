<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorCommandCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\CommandCommand;
use Drupal\Console\Test\Builders\a;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\CommandDataProviderTrait;

class GeneratorCommandCommandTest extends GenerateCommandTest
{
    use CommandDataProviderTrait;

    /**
     * Command generator test
     *
     * @param string $module
     * @param string $name
     * @param string $class
     * @param bool $containerAware
     *
     * @dataProvider commandData
     */
    public function testGenerateCommand(
        $module,
        $name,
        $class,
        $containerAware
    ) {
        $manager = a::extensionManager();
        $generator = a::commandGenerator();
        $command = new CommandCommand(
            $generator->reveal(),
            $manager,
            new Validator($manager),
            new StringConverter()
        );

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
                '--module' => $module,
                '--name' => $name,
                '--class' => $class,
                '--container-aware' => $containerAware,
            ],
            ['interactive' => false]
        );

        $generator
            ->generate($module, $name, $class, $containerAware, [])
            ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }
}
