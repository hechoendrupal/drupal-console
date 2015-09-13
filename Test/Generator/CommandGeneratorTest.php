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
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->translator);
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $name,
            $class,
            $containerAware
        );
    }
}
