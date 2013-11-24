<?php
namespace Drupal\AppConsole\Command\Helper;

use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Helper\ProgressHelper;
use \Symfony\Component\Console\Helper\Helper;
use \Symfony\Component\Finder\Finder;
use \InvalidArgumentException;
use \SplFileInfo;

class BootstrapFinderHelper extends Helper {

  /**
   * @var Finder
   */
  protected $finder;

  /**
   * @param Finder $finder
   */
  public function __construct(Finder $finder) {
    $this->finder = $finder;
  }

  public function findBootstrapFile(OutputInterface $output) {
    $output->writeln('<info>Finding bootstrap.inc file...</info>');

    $currentPath = getcwd() . '/';
    $relativePath = '';
    $filesFound = 0;

    while ($filesFound === 0) {
      $path = $currentPath . $relativePath;
      $output->writeln("Searching in <info>$path</info>");

      $iterator = $this->finder
                       ->files()
                       ->name('bootstrap.inc')
                       ->in($path);

      $filesFound = $iterator->count();

      if ($filesFound === 0) {
        $relativePath .= '../';

        if (realpath($currentPath . $relativePath) === '/') {

          throw new InvalidArgumentException('Cannot find Drupal boostrap file.');
        }
      }
    }

    foreach ($iterator as $file) {
      $bootstrapRealPath = $file->getRealpath();
      break;
    }
    $output->writeln('<info>File bootstrap.inc file found!</info>');

    return $bootstrapRealPath;
  }

  public function getName() {

    return 'finder';
  }
}
