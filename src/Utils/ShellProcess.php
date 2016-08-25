<?php
namespace Drupal\Console\Utils;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class ShellProcess
 * @package Drupal\Console\Utils
 */
class ShellProcess
{
    /**
     * @var string
     */
    protected $appRoot;
    /**
     * @var ShellProcess
     */
    private $process;

    /**
     * Process constructor.
     * @param string $appRoot
     */
    public function __construct($appRoot)
    {
        $this->appRoot = $appRoot;
    }

    /**
     * @param $command string
     *
     * @throws ProcessFailedException
     *
     * @return Process
     */
    public function exec($command)
    {
        $this->process = new Process($command);
        $this->process->setWorkingDirectory($this->appRoot);
        $this->process->enableOutput();
        $this->process->setTimeout(null);
        $this->process->start();

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
