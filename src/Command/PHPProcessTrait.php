<?php

/**
 * @file
 * Contains Drupal\Console\Command\PHPProcessTrait.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class PHPProcessTrait
 * @package Drupal\Console\Command
 */
trait PHPProcessTrait
{
    /**
     * @param $command
     * @return string
     *
     * @throws ProcessFailedException
     */
    protected function execProcess($command)
    {
        $rootPath = $this->getDrupalHelper()->getRoot();
        $phpScript = exec($command);
        $phpProcess = new PhpProcess($phpScript, $rootPath);
        $phpProcess->run();

        if (!$phpProcess->isSuccessful()) {
            throw new ProcessFailedException($phpProcess);
        }

        return $phpProcess->getOutput();
    }
}
