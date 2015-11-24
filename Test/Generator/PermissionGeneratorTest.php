<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\PermissionGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\PermissionGenerator;
use Drupal\Console\Test\DataProvider\PermissionDataProviderTrait;

class PermissionGeneratorTest extends GeneratorTest
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
        $generator = new PermissionGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $permissions
        );

        $this->assertTrue(
            file_exists($generator->getSite()->getModulePath($module).'/'.$module.'.permissions.yml'),
            sprintf('%s does not exist', $module.'.permissions.yml')
        );
    }
}
