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

        $this->process = new Process($command, $rootPath);
        $this->process->setWorkingDirectory($rootPath);
        $this->process->setEnhanceSigchildCompatibility(true);
        $this->process->setTty('true');
        $this->process->run();

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
