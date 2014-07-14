<?php
/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\GeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Symfony\Component\Filesystem\Filesystem;

abstract class GeneratorTest extends \PHPUnit_Framework_TestCase 
{
  var $dir;

  public function setUp()
  {
    $this->setUpTemporalDirectory();
  }

  public function setUpTemporalDirectory()
  {
    $this->dir = sys_get_temp_dir() . "/modules";
  }

  public function getSkeletonDirs()
  {
    $skeletonDirs = [];
    $skeletonDirs[] = __DIR__.'/../../src/Resources/skeleton';
    $skeletonDirs[] = __DIR__.'/../../src/Resources';

    return $skeletonDirs;
  }
}
