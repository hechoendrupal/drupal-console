<?php
/**
 * @file
 * Contains Drupal\AppConsole\Test\Generator\GeneratorTest.
 */

namespace Drupal\AppConsole\Test\Generator;

use Symfony\Component\Filesystem\Filesystem;

abstract class GeneratorTest extends \PHPUnit_Framework_TestCase 
{
  public function setUp()
  {
    $this->dir = sys_get_temp_dir() . "/module";
    
    $this->filesystem = new Filesystem();
    $this->filesystem->remove($this->dir);
  }

  public function tearDown()
  {
    $this->filesystem->remove($this->dir);
  }
}