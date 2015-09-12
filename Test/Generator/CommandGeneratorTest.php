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
        $generator = new CommandGenerator();
        $generator->setSkeletonDirs(__DIR__ . '/../../templates');
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $name,
            $class,
            $containerAware
        );
    }
}
