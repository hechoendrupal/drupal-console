<?php
namespace Drupal\Console\Utils;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drupal\Console\Utils\Site;

/**
 * Class ShellProcess
 * @package Drupal\Console\Utils
 */
class ShellProcess
{
    /* @var Site */
    protected $site;

    /**
     * @var ShellProcess
     */
    protected $process;

    /**
     * Process constructor.
     * @param Site $site
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    /**
     * @param $command
     *
     * @throws ProcessFailedException
     *
     * @return Process
     */
    public function exec($command)
    {
        $rootPath = $this->site->getRoot();

        $this->process = new Process($command);
        $this->process->setWorkingDirectory($rootPath);
        $this->process->enableOutput();
        $this->process->setTimeout(null);
        $this->process->run(function ($type, $buffer) {
          //@TODO: use $io
          echo $buffer;
        });

        if (!$this->process->isSuccessful()) {
            throw new ProcessFailedException($this->process);
        }

        return $this->process->isSuccessful();
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->process->getOutput();
    }
}
