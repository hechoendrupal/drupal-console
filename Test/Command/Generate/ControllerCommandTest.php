<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorControllerCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;

use Drupal\Console\Command\Generate\ControllerCommand;
use Drupal\Console\Generator\ControllerGenerator;
use Drupal\Console\Test\Builders\a as an;
use Drupal\Console\Utils\ChainQueue;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Routing\RouteProvider;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\ControllerDataProviderTrait;

class ControllerCommandTest extends GenerateCommandTest
{
    use ControllerDataProviderTrait;

    /**
     * Controller generator test
     *
     * @param $module
     * @param $class_name
     * @param $routes
     * @param $test
     * @param $services
     *
     * @dataProvider commandData
     */
    public function testGenerateController(
        $module,
        $class_name,
        $routes,
        $test,
        $services
    )
    {
        $generator = an::controllerGenerator();
        $manager = an::extensionManager();

        $command = new ControllerCommand(
            $manager,
            new ControllerGenerator($manager),
            new StringConverter(),
            new Validator($manager),
            new RouteProvider(
                ,an::stateInterface()->reveal(),an::currentPathStack()->reveal(),an::cacheBackendInterface()->reveal(),an::inboundPathProcessorInterface()->reveal(),an::cacheTagsInvalidator()->reveal(),'router'),
            new ChainQueue()
        );

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
                '--module' => $module,
                '--class' => $class_name,
                '--routes' => $routes,
                '--test' => $test,
                '--services' => $services,
            ],
            ['interactive' => false]
        );

        $generator
            ->generate($module, $class_name, $routes, $test, $services);
        $this->assertEquals(0, $code);

    }
}


