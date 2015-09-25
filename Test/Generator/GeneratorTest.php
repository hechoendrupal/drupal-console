<?php
/**
 * @file
 * Contains Drupal\Console\Test\Generator\GeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Test\BaseTestCase;

abstract class GeneratorTest extends BaseTestCase
{
    public function getSkeletonDirs()
    {
        $skeletonDirs[] = __DIR__ . '/../../templates';

        return $skeletonDirs;
    }
}
