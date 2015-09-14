<?php

/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\PermissionGeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Generator\PermissionGenerator;
use Drupal\AppConsole\Test\DataProvider\PermissionDataProviderTrait;

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
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
        $generator->setHelpers($this->getHelperSet());

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
