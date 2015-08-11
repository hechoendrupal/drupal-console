<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Test\Generator\CommandGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\CommandGenerator;
use Drupal\AppConsole\Test\DataProvider\CommandDataProviderTrait;

class CommandGeneratorTest extends GeneratorTest
{
    use CommandDataProviderTrait;

    /**
     * @param $module
     * @param $command
     * @param $class_name
     * @param $container
     *
     * @dataProvider commandData
     */
    public function testCommandController(
        $module,
        $command,
        $class_name,
        $container
    ) {
        $generator = new CommandGenerator();
        $generator->setSkeletonDirs(__DIR__ . '/../../templates');
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $command,
            $class_name,
            $container
        );
    }
}
