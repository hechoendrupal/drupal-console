<?php

/**
 * @file
 * Contains \Drupal\Console\Test\Generator\CommandGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\CommandGenerator;
use Drupal\Console\Test\DataProvider\CommandDataProviderTrait;

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
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $name,
            $class,
            $containerAware
        );
    }
}
