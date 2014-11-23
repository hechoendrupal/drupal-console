<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\BootstrapFinderHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Finder\Finder;

class BootstrapFinderHelper extends Helper
{
  /**
   * @var Finder
   */
  protected $finder;

  /**
   * @param Finder $finder
   */
  public function __construct(Finder $finder)
  {
    $this->finder = $finder;
  }

  public function findBootstrapFile()
  {
    $currentPath = getcwd() . '/';
    $relativePath = '';
    $filesFound = 0;
    $iterator = false;

    while ($filesFound === 0) {
      $path = $currentPath . $relativePath . 'core/vendor';

      try {
        $iterator = $this->finder
                         ->files()
                         ->name('autoload.php')
                         ->in($path)
                         ->depth('< 1');
        $filesFound = $iterator->count();
      } catch (\InvalidArgumentException $e) {
        $relativePath .= '../';

        if (realpath($currentPath . $relativePath) === '/') {
          break;
        }
      }
    }

    if ($iterator) {
      foreach ($iterator as $file) {
        $bootstrapRealPath = $file->getRealpath();
        break;
      }
      return $bootstrapRealPath;
    }
    else {
      return false;
    }
  }

  public function getName()
  {
    return 'finder';
  }
}
