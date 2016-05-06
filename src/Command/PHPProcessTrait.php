<?php

/**
 * @file
 * Contains Drupal\Console\Command\PHPProcessTrait.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Process\PhpProcess;

/**
 * Class PHPProcessTrait
 * @package Drupal\Console\Command
 */
trait PHPProcessTrait
{
    /**
     * @param string $cmd
     *
     * @return mixed
     */
    protected function ExecProcess($cmd)
    {
          $rootPath = $this->getDrupalHelper()->getRoot();
          $php_script = exec( $cmd );
          $phpProcess = new PhpProcess($php_script, $rootPath);
          $phpProcess->run();

          // executes after the command finishes
          if (!$phpProcess->isSuccessful()) {
              throw new ProcessFailedException($phpProcess);
          }

          return $phpProcess->getOutput();

    }
}
