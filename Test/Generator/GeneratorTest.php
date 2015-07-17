<?php
/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\GeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Drupal\AppConsole\Test\BaseTestCase;

abstract class GeneratorTest extends BaseTestCase
{
    public function getSkeletonDirs()
    {
        $skeletonDirs[] = __DIR__ . '/../../templates';

        return $skeletonDirs;
    }

    public function getModulePath($module)
    {
        return $this->dir . '/' . $module;
    }
}
